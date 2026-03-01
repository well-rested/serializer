<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use InvalidArgumentException;
use WellRested\Serializer\Attributes\Field;
use WellRested\Serializer\Attributes\GetVia;
use WellRested\Serializer\Attributes\Hoist;
use WellRested\Serializer\Attributes\SetVia;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionAbstract;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionFactoryInterface;
use WellRested\Serializer\ClassAnalyserInterface;
use WellRested\Serializer\NamingStrategyInterface;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;

class ClassAnalyser implements ClassAnalyserInterface
{
	public function __construct(
		protected NamingStrategyInterface $namingStrategy,
		protected TypeDefinitionFactoryInterface $typeDefinitionFactory,
	) {}

	public function analyse(string $class, bool $allowsNull = false, ?ClassAnalysisContext $context = null): ClassAnalyses
	{
		if (!class_exists($class, true)) {
			throw new InvalidArgumentException("value is not a class: $class");
		}

		if (null === $context) {
			$context = (new ClassAnalysisContext());
		}

		$context = $context->addLink(new AnalysisLink(
			className: $class,
			allowsNull: $allowsNull,
		));

		$analyses = new ClassAnalyses();
		$refl = $this->reflect($class);

		$properties = new PropertyAnalyses();

		foreach ($refl->getProperties() as $property) {
			$analysis = $this->analyseProperty($analyses, $property, $context);

			$properties->add(
				$analysis->getName(),
				$analysis,
			);
		}

		$analyses->add($class, new ClassAnalysis(
			name: $class,
			properties: $properties,
			attributes: $this->getAttributes($refl),
		));

		return $analyses;
	}

	protected function analyseProperty(ClassAnalyses $classAnalyses, ReflectionProperty $property, ClassAnalysisContext $context): PropertyAnalysis
	{
		$type = PropertyTypeName::fromReflectionProperty($property);

		$possibleTypes = match (true) {
			/* @var ReflectionUnionType|ReflectionIntersectionType $property */
			PropertyTypeName::Union === $type || PropertyTypeName::Intersection === $type => array_map(
				fn(ReflectionNamedType $type) => $type->getName(),
				$property->getType()->getTypes(),
			),
			PropertyTypeName::Complex === $type => [
				$this->getPropertyTypeName($property),
			],
			PropertyTypeName::Option === $type => $this->getTypesFromFieldAttribute($property),
			PropertyTypeName::Array === $type => $this->getTypesFromFieldAttribute($property),
			default => [
				$type->value,
			],
		};

		if ($this->propertyAllowsNull($property) && !$type->allowsNull()) {
			$possibleTypes[] = PropertyTypeName::Null->value;
		}

		$this->guardAgainstInfiniteRecursion($type, $property, $context);

		foreach ($possibleTypes as $possibleType) {
			if (class_exists($possibleType) && !$context->hasLink($possibleType)) {
				$classAnalyses->merge(
					$this->analyse(
						$possibleType,
						in_array(PropertyTypeName::Null->value, $possibleTypes),
						$context,
					),
				);
			}
		}

		if (PropertyTypeName::Complex == $type && !$context->hasLink($this->getPropertyTypeName($property))) {
			$classAnalyses->merge(
				$this->analyse(
					class: $property->getType()->getName(),
					allowsNull: $property->getType()->allowsNull(),
					context: $context,
				),
			);
		}

		$hasDefault = $property->hasDefaultValue();
		$defaultValue = $property->getDefaultValue();

		if ($property->isPromoted()) {
			$defaultValue = null;
			/** @var ReflectionParameter[] $constructorParams */
			$constructorParams = $property->getDeclaringClass()->getConstructor()->getParameters();

			foreach ($constructorParams as $param) {
				if ($param->getName() == $property->getName()) {
					$hasDefault = $param->isDefaultValueAvailable();

					if ($hasDefault) {
						$defaultValue = $param->getDefaultValue();
					}
				}
			}
		}

		return new PropertyAnalysis(
			name: $property->getName(),
			serializedName: $this->getNameForProperty($property),
			type: $type,
			possibleConcreteTypes: $possibleTypes,
			setterStrategy: $this->determinePropertySetterStrategy($property),
			getterStrategy: $this->determinePropertyGetterStrategy($property),
			hasDefault: $hasDefault,
			defaultValue: $defaultValue,
			hoistStrategy: $this->determineHoistStrategy($classAnalyses, $property, $type, $possibleTypes),
			attributes: $this->getAttributes($property),
			_type: $this->determineTypeFromProperty($property),
		);
	}

	protected function determineTypeFromProperty(ReflectionProperty $property): TypeDefinitionAbstract
	{
		return $this->typeDefinitionFactory->fromReflectionProperty($property);
	}

	protected function getAttributes(ReflectionProperty|ReflectionClass $subject): Attributes
	{
		$attributes = new Attributes();
		foreach ($subject->getAttributes() as $attribute) {
			$attributes->add($attribute->newInstance());
		}

		return $attributes;
	}

	/**
	 * Note that we expect this to run after all possible types have been analysed.
	 * This is done in the analyseProperty method so should be safe.
	 */
	protected function determineHoistStrategy(ClassAnalyses $classAnalyses, ReflectionProperty $property, PropertyTypeName $type, array $possibleTypes): HoistStrategy
	{
		$attr = $this->getHoistAttribute($property);

		if (null === $attr) {
			return new HoistStrategy(
				enabled: false,
			);
		}

		if (PropertyTypeName::Complex !== $type && PropertyTypeName::Union !== $type) {
			throw new RuntimeException('cannot hoist a non object');
		}

		foreach ($possibleTypes as $possibleType) {
			if (!class_exists($possibleType) && $possibleType !== PropertyTypeName::Null->value) {
				throw new RuntimeException('all target types for hoistable property must be objects: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
			}

			$analysis = $classAnalyses->get($possibleType);

			if (!$analysis->getProperties()->has($attr->property)) {
				throw new RuntimeException('hoist target not found on type (' . $possibleType . '->' . $attr->property . ') for property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
			}

			if (!$analysis->getProperties()->get($attr->property)->canGetPropertyValue()) {
				throw new RuntimeException('hoist target not retrievable (' . $possibleType . '->' . $attr->property . ') for property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
			}
		}

		return new HoistStrategy(
			enabled: true,
			property: $attr->property,
		);
	}

	protected function propertyAllowsNull(ReflectionProperty $property): bool
	{
		$type = $property->getType();

		if (null === $type) {
			return true;
		}

		return $type->allowsNull();
	}

	protected function determinePropertyGetterStrategy(ReflectionProperty $property): GetPropertyStrategy
	{
		if ($property->isPublic()) {
			return new GetPropertyStrategy(
				method: GetPropertyStrategyMethod::PublicGetter,
			);
		}

		$getViaReflAttr = $property->getAttributes(GetVia::class)[0] ?? null;

		if (null === $getViaReflAttr) {
			return new GetPropertyStrategy(
				method: GetPropertyStrategyMethod::NotAvailable,
			);
		}

		$getViaAttr = $getViaReflAttr->newInstance();

		if (!$property->getDeclaringClass()->hasMethod($getViaAttr->method)) {
			throw new RuntimeException('method defined in GetVia attribute not found on class for property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		$method = $property->getDeclaringClass()->getMethod($getViaAttr->method);

		if (!$method->isPublic()) {
			throw new RuntimeException('method defined in GetVia attribute is not public, analysing: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		$params = $method->getParameters();

		if (!empty($params)) {
			throw new RuntimeException('invalid number of args for getter method, found ' . count($params) . ' (expected exactly 0) : ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		if ($method->getReturnType() != $property->getType()) {
			throw new RuntimeException('return type from setter method, must match type of property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		return new GetPropertyStrategy(
			method: GetPropertyStrategyMethod::GetterMethod,
			getterMethod: $method->name,
		);
	}

	protected function determinePropertySetterStrategy(ReflectionProperty $property): SetPropertyStrategy
	{
		if ($property->isPromoted()) {
			/** @var ReflectionParameter|null */
			$constructorParameter = null;

			foreach ($property->getDeclaringClass()->getConstructor()->getParameters() as $parameter) {
				if ($parameter->getName() != $property->getName()) {
					continue;
				}
				$constructorParameter = $parameter;
			}

			// Not sure this could actually ever happen...but we should handle it in case.
			if (null === $constructorParameter) {
				throw new RuntimeException('Failed to find constructor param for promoted property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
			}

			return new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::ConstructorArgument,
				constructorIndex: $constructorParameter->getPosition(),
			);
		}

		if ($property->isReadOnly() || $property->getDeclaringClass()->isReadOnly()) {
			throw new RuntimeException('property or class is readonly so all properties be promoted via constructor: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		if ($property->isPublic() && !$property->isPrivateSet() && !$property->isProtectedSet()) {
			return new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::PublicSetter,
			);
		}

		$setViaReflAttr = $property->getAttributes(SetVia::class)[0] ?? null;

		if (null === $setViaReflAttr) {
			return new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::NotAvailable,
			);
			// throw new RuntimeException('could not determine how to set property: '.$property->getDeclaringClass()->getName().'->'.$property->getName());
		}

		$setViaAttr = $setViaReflAttr->newInstance();

		if (!$property->getDeclaringClass()->hasMethod($setViaAttr->method)) {
			throw new RuntimeException('method defined in SetVia attribute not found on class for property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		$method = $property->getDeclaringClass()->getMethod($setViaAttr->method);

		if (!$method->isPublic()) {
			throw new RuntimeException('method defined in SetVia attribute is not public, analysing: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		$params = $method->getParameters();

		if (1 != count($params)) {
			throw new RuntimeException('invalid number of args for setter method, found ' . count($params) . ' (expected exactly 1) : ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		$param = $params[0];

		if ($param->getType()->getName() !== $property->getType()->getName()) {
			throw new RuntimeException('only argument to setter method, must match type of property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		return new SetPropertyStrategy(
			method: SetPropertyStrategyMethod::SetterMethod,
			setterMethod: $method->name,
		);
	}

	protected function guardAgainstInfiniteRecursion(PropertyTypeName $type, ReflectionProperty $property, ClassAnalysisContext $context): void
	{
		if (PropertyTypeName::Complex !== $type) {
			return;
		}
		$propType = $this->getPropertyTypeName($property);

		if ($propType === $property->getDeclaringClass()->getName() && !$property->getType()->allowsNull()) {
			throw new RuntimeException('Infinite recursion found in class: ' . $context->rootLink()?->getClassName());
		}

		$propClass = new ReflectionClass($propType);

		foreach ($propClass->getProperties() as $prop) {
			/*
			 * Recursion hurts my brain, so some notes...
			 *
			 * Basically if we get here and the property is nullable, then we don't need to worry about inifite recursion.
			 *
			 * If the property is not nullable, and there is a link in the chain to the type of the property, then it could
			 * be a problem. But it's only a problem if there is no other nullable link in the chain, as it could be that
			 * somewhere else in the chain will eventually be null so we can still construct the object.
			 *
			 * @see lib/OpenApi/Tests/Unit/Serialization/Analysis/ClassAnalyserTest.php::testRecursion* methods
			 */
			if (!$prop->getType()->allowsNull() && $context->hasLink($prop->getType()->getName()) && !$context->hasANullableLink()) {
				throw new RuntimeException('Infinite recursion found in class: ' . $context->rootLink()?->getClassName());
			}
		}
	}

	/**
	 * @return string[]
	 */
	protected function getTypesFromFieldAttribute(ReflectionProperty $property): array
	{
		$attr = $property->getAttributes(Field::class)[0] ?? null;

		$field = null === $attr ? null : $attr->newInstance();

		if (null === $field || null === $field->type) {
			return [PropertyTypeName::Any->value];
		}

		return explode('|', $field->type);
	}

	protected function getHoistAttribute(ReflectionProperty $property): ?Hoist
	{
		$attr = $property->getAttributes(Hoist::class)[0] ?? null;

		return null === $attr ? null : $attr->newInstance();
	}

	protected function getNameForProperty(ReflectionProperty $property): string
	{
		$attr = $property->getAttributes(Field::class)[0] ?? null;

		$field = null === $attr ? null : $attr->newInstance();

		return $field?->name ?? $this->namingStrategy->convert($property->getName());
	}

	protected function reflect(string $class): ReflectionClass
	{
		return new ReflectionClass($class);
	}

	protected function getPropertyTypeName(ReflectionProperty $prop): string
	{
		$typeName = $prop->getType()->getName();

		if ('self' === $typeName) {
			return $prop->getDeclaringClass()->getName();
		}

		return $typeName;
	}
}
