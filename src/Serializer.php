<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Analysis\ClassAnalysis;
use WellRested\Serializer\Analysis\PropertyAnalysis;
use WellRested\Serializer\Analysis\TypeDefinitions\ArrayTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\BoolTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\ClassTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\CoercerInterface;
use WellRested\Serializer\Analysis\TypeDefinitions\FloatTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\IntegerTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\MixedTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\NullTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\ObjectTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\OptionTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\StringTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionAbstract;
use WellRested\Serializer\Analysis\TypeDefinitions\UnionTypeDefinition;
use WellRested\Serializer\Exceptions\HoistTargetNotFoundException;
use WellRested\Serializer\Exceptions\IncorrectTypeForHoistException;
use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use stdClass;

class Serializer implements SerializerInterface, DeserializerInterface
{
	protected FieldErrors $fieldErrors;

	protected string $rootType;

	public function __construct(
		protected ClassAnalyserInterface $analyser,
		protected CoercerInterface $coercer,
	) {
		$this->fieldErrors = new FieldErrors();
	}

	public function getRaisedErrors(): FieldErrors
	{
		return $this->fieldErrors;
	}

	/**
	 * Returns either an array or an stdClass. The stdClass is only really there
	 * to support the scenario where we have an empty object, but an empty array
	 * when converted to json would be just an array rather than '{}' which is
	 * what we'd actually want.
	 */
	public function serialize(object $subject): array|stdClass
	{
		$this->rootType = get_class($subject);

		$value = $this->_serialize($subject);

		return $value;
	}

	public function _serialize(object $subject, $path = ''): array|stdClass
	{
		if ($subject instanceof stdClass) {
			return json_decode(json_encode($subject), true);
		}

		$subjectClass = get_class($subject);
		$analysis = $this->analyser->analyse($subjectClass)->get($subjectClass);

		$value = $this->serializeForClassAnalysis($analysis, $subject, $path);

		return $value->isDefined() ? $value->get() : new stdClass();
	}

	protected function serializeForClassAnalysis(ClassAnalysis $analysis, mixed $value, string $path): Option
	{
		if ($value instanceof Option && !$value->isDefined()) {
			return None::create();
		}

		if ($value instanceof Option) {
			$value = $value->get();
		}

		$data = [];

		foreach ($analysis->getProperties() as $property) {
			$serializedValue = $this->serializeProperty($property, $value, $path);

			if (!$serializedValue->isDefined()) {
				continue;
			}

			$data[$property->getSerializedName()] = $serializedValue->get();
		}

		if (empty($data)) {
			$data = new stdClass();
		}

		return new Some($data);
	}

	protected function serializeProperty(PropertyAnalysis $analysis, mixed $container, string $path): Option
	{
		$propertyPath = $this->concatPath($path, $analysis->getName());
		$propValue = $this->retrievePropertyValueFromContainer($analysis, $container);

		return match (true) {
			$analysis->shouldHoist() => $this->serializeHoistedProperty($analysis, $propValue, $propertyPath),
			default => $this->serializeValue($analysis->getTypeDefinition(), $propValue, $propertyPath),
		};
	}

	protected function serializeHoistedProperty(PropertyAnalysis $analysis, mixed $propValue, string $path): Option
	{
		$type = $analysis->getTypeDefinition();

		if (!$this->canHoistValueforProperty($analysis)) {
			throw new IncorrectTypeForHoistException($this->rootType, $path, $type->getName());
		}

		/**
		 * The canHoistValueforProperty check tells us this, but that may change
		 * with support for optional hoisted properties and poymorphism.
		 *
		 * @var ClassDefinitionType $type
		 */
		$hoistTarget = $type->getName();

		$toHoist = $analysis->hoistProperty();
		$targetAnalysis = $this->analyser->analyse($hoistTarget)->get($hoistTarget);

		if (!$targetAnalysis->getProperties()->has($toHoist)) {
			throw new HoistTargetNotFoundException($this->rootType, $path, $toHoist);
		}

		$hoistContainer = $this->serializeForClassAnalysis($targetAnalysis, $propValue, $this->concatPath($path, $targetAnalysis->getName()));

		if (!$hoistContainer->isDefined()) {
			return None::create();
		}

		$hoistContainerValue = $hoistContainer->get();
		if ($hoistContainerValue instanceof stdClass || !array_key_exists($toHoist, $hoistContainerValue)) {
			// TODO: handle optional hoisted properties?
			return new Some(null);
		}

		return new Some($hoistContainerValue[$toHoist]);
	}

	/**
	 * Later we'll probably support more here.
	 */
	protected function canHoistValueforProperty(PropertyAnalysis $analysis): bool
	{
		$type = $analysis->getTypeDefinition();

		return $type->is(ClassTypeDefinition::class);
	}

	protected function serializeValue(TypeDefinitionAbstract $type, mixed $value, $path): Option
	{
		$value = match (true) {
			$type->is(OptionTypeDefinition::class) && !$value->isDefined() => None::create(),
			$type->is(OptionTypeDefinition::class) && $value->isDefined() => $this->serializeValue($type->getWrappedType(), $value->get(), $path),
			$type->is(ArrayTypeDefinition::class) => new Some($this->serializeArrayValue($type, $value, $path)),
			$type->is(ClassTypeDefinition::class) => $this->serializeForClassAnalysis(
				$this->analyser->analyse($type->getName())->get($type->getName()),
				$value,
				$path,
			),
			default => new Some($value),
		};

		return $value;
	}

	protected function serializeArrayValue(ArrayTypeDefinition $arrayType, array $values, string $path): array
	{
		$items = array_map(
			fn(mixed $item, int $index) => $this->serializeValue(
				$arrayType->getItemType(),
				$item,
				$this->concatPath($path, (string) $index),
			),
			$values,
			array_keys($values),
		);

		// shouldn't really be needed cause you can't really have an option in an array, it just wouldn't be there?
		return array_map(
			fn(Option $opt) => $opt->get(),
			array_filter($items, fn(Option $opt) => $opt->isDefined()),
		);
	}

	protected function retrievePropertyValueFromContainer(PropertyAnalysis $analysis, object $container): mixed
	{
		if ($analysis->shouldUseGetterMethod()) {
			return $container->{$analysis->getGetterMethod()}();
		}

		return $container->{$analysis->getName()};
	}

	/**
	 * @template T
	 *
	 * @param class-string<T> $target
	 *
	 * @return Option<T>
	 */
	public function deserialize(array $data, string $target): Option
	{
		$this->rootType = $target;
		$this->fieldErrors = new FieldErrors();

		$value = $this->_deserialize($data, $target);

		if (!$this->fieldErrors->isEmpty()) {
			return None::create();
		}

		return $value;
	}

	protected function _deserialize(array $data, string $target, $path = ''): Option
	{
		$analyses = $this->analyser->analyse($target);
		$analysis = $analyses->get($target);

		$targetClass = $analysis->getName();

		$args = $this->gatherConstructorArgs($data, $analysis, $path);

		if (!$args->isDefined()) {
			return None::create();
		}

		$argsValue = array_map(fn(Option $opt) => $opt->get(), $args->get());
		$instance = new $targetClass(...$argsValue);

		$this->setNonConstructorProperties($instance, $data, $analysis, $path);

		return new Some($instance);
	}

	protected function concatPath(string $path, string $part): string
	{
		return '' == $path ? $part : "$path.$part";
	}

	protected function setNonConstructorProperties(object $instance, array $data, ClassAnalysis $analysis, string $path): void
	{
		foreach ($analysis->getProperties() as $property) {
			if ($property->shouldPassThroughConstructor()) {
				continue;
			}

			$value = $this->buildPropertyValue(
				$property,
				$data,
				$this->concatPath($path, $property->getSerializedName()),
			);

			if (!$value->isDefined() && !$property->isOptional()) {
				// if it's a None, then we couldn't determine a value, and there was an error
				continue;
			}

			if ($property->shouldUsePublicSetter()) {
				$instance->{$property->getName()} = $property->isOptional() ? $value : $value->get();
				continue;
			}

			if ($property->shouldUseSetterMethod()) {
				$instance->{$property->getSetterMethod()}(
					$property->isOptional() ? $value : $value->get()
				);
			}
		}
	}

	protected function gatherConstructorArgs(array $data, ClassAnalysis $analysis, string $path): Option
	{
		$args = [];

		$argMissing = false;

		foreach ($analysis->getProperties() as $property) {
			if (!$property->shouldPassThroughConstructor()) {
				continue;
			}

			$value = $this->buildPropertyValue(
				$property,
				$data,
				$this->concatPath($path, $property->getSerializedName()),
			);

			if (!$value->isDefined() && !$property->isOptional()) {
				$argMissing = true;
				continue;
			}

			// If it's optional, we wrap it in another some, so that when it's
			// unwrapped later we pass an option
			$args[$property->getConstructorIndex()] = $property->isOptional() ? new Some($value) : $value;
		}

		if ($argMissing) {
			return None::create();
		}

		return new Some($args);
	}

	protected function buildPropertyValue(PropertyAnalysis $property, array $data, string $path): Option
	{
		$serializedName = $property->getSerializedName();
		$valueIsPresent = array_key_exists($serializedName, $data);
		$type = $property->getTypeDefinition();

		if ($property->isOptional() && !$valueIsPresent) {
			return None::create();
		}

		$value = $data[$serializedName] ?? null;

		if (!$valueIsPresent) {
			if ($property->hasDefault()) {
				return new Some($property->getDefault());
			}

			$this->fieldErrors->add(new FieldError(
				location: $path,
				message: 'value is required',
				value: None::create(),
			));

			return None::create();
		}

		if (null === $value) {
			if ($property->allowsNull()) {
				return new Some(null);
			}

			$this->recordInvalidTypeError($path, $property->getTypeDefinition(), $value);

			return None::create();
		}

		return $this->convertPropertyValueToCompatibleType($type, $value, $path);
	}

	protected function recordInvalidTypeError(string $path, TypeDefinitionAbstract $type, mixed $value): void
	{
		$this->fieldErrors->add(new FieldError(
			location: $path,
			message: 'invalid type, expected: ' . $type->getName(),
			value: new Some($value),
		));
	}

	protected function convertPropertyValueToCompatibleType(TypeDefinitionAbstract $type, mixed $value, string $path): Option
	{
		$isCompatible = $this->valueIsCompatibleWithType($type, $value, $path);

		if ($isCompatible) {
			return match (true) {
				$type->is(OptionTypeDefinition::class) => $this->convertValueForOptionType($type, $value, $path),
				$type->is(UnionTypeDefinition::class) => $this->convertValueForUnionType($type, $value, $path),
				$type->is(ArrayTypeDefinition::class) => $this->convertValueForArrayType($type, $value, $path),
				$type->is(ClassTypeDefinition::class) => $this->_deserialize($value, $type->getName(), $path),
				$type->is(ObjectTypeDefinition::class) => new Some((object) $value),
				default => new Some($value),
			};
		}

		if ($this->coercer->canCoerce($type, $value)) {
			return new Some($this->coercer->coerce($type, $value));
		}

		$this->recordInvalidTypeError($path, $type, $value);

		return None::create();
	}

	protected function convertValueForOptionType(OptionTypeDefinition $type, mixed $value, string $path): Option
	{
		$newValue = $this->convertPropertyValueToCompatibleType($type->getWrappedType(), $value, $path);

		return $newValue;
	}

	protected function convertValueForUnionType(UnionTypeDefinition $unionType, mixed $value, string $path): Option
	{
		$concrete = $this->getConcreteForUnion($unionType, $value);

		if (null === $concrete) {
			return None::create();
		}

		return $this->convertPropertyValueToCompatibleType($concrete, $value, $path);
	}

	protected function convertValueForArrayType(ArrayTypeDefinition $arrayType, mixed $values, string $path): Option
	{
		$itemType = $arrayType->getItemType();

		$results = [];

		foreach ($values as $index => $value) {
			$newValue = $this->convertPropertyValueToCompatibleType($itemType, $value, $this->concatPath($path, (string) $index));

			if (!$newValue->isDefined()) {
				return None::create();
			}

			$results[] = $newValue->get();
		}

		return new Some($results);
	}

	protected function valueIsCompatibleWithType(TypeDefinitionAbstract $type, mixed $value): bool
	{
		return match (true) {
			$type->is(UnionTypeDefinition::class) => null !== $this->getConcreteForUnion($type, $value),
			$type->is(IntegerTypeDefinition::class) => is_int($value),
			$type->is(StringTypeDefinition::class) => is_string($value),
			$type->is(BoolTypeDefinition::class) => is_bool($value),
			$type->is(FloatTypeDefinition::class) => is_float($value),
			$type->is(ArrayTypeDefinition::class) => is_array($value) && array_is_list($value),
			$type->is(NullTypeDefinition::class) => is_null($value),
			$type->is(MixedTypeDefinition::class) => true,
			$type->is(ObjectTypeDefinition::class),
			$type->is(ClassTypeDefinition::class) => is_array($value),
			// TODO: help static analysis out here, doesn't know that getWrappedType does in fact exist...
			$type->is(OptionTypeDefinition::class) => $this->valueIsCompatibleWithType($type->getWrappedType(), $value),

			default => false,
		};
	}

	protected function getConcreteForUnion(UnionTypeDefinition $unionType, mixed $value): ?TypeDefinitionAbstract
	{
		foreach ($unionType->getPossibleTypes() as $type) {
			if ($this->valueIsCompatibleWithType($type, $value)) {
				return $type;
			}
		}

		return null;
	}
}
