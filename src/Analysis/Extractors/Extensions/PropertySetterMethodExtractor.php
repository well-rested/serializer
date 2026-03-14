<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Attributes\SetVia;
use WellRested\Serializer\Util\MixedDictionary;

class PropertySetterMethodExtractor implements ExtendsPropertyExtraction
{
	public const EXTENSION_NAME = "builtin.property_setter_method_extractor";

	public function extract(ReflectionProperty $property): MixedDictionary
	{
		$dict = new MixedDictionary();

		$strategy = match (true) {
			($property->getAttributes(SetVia::class)[0] ?? null) !== null => $this->handleSetViaAttribute($property),
			$property->isPromoted() => $this->handlePromoted($property),
			$property->isReadOnly() || $property->getDeclaringClass()->isReadOnly() => throw new RuntimeException('property or class is readonly so all properties be promoted via constructor: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName()),
			$property->isPublic() && !$property->isPrivateSet() && !$property->isProtectedSet() => new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::PublicSetter,
			),
			default => throw new RuntimeException('could not determine viable setter method for property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName()),
		};

		$dict->add('value', $strategy);

		return $dict;
	}

	protected function handlePromoted(ReflectionProperty $property): SetPropertyStrategy
	{
		/** @var ReflectionParameter|null */
		$constructorParameter = null;

		$constructorMethod = $property->getDeclaringClass()->getConstructor();

		if ($constructorMethod === null) {
			throw new RuntimeException('no constructor');
		}

		foreach ($constructorMethod->getParameters() as $parameter) {
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

	protected function handleSetViaAttribute(ReflectionProperty $property): SetPropertyStrategy
	{
		$setViaReflAttr = $property->getAttributes(SetVia::class)[0] ?? null;

		if ($setViaReflAttr === null) {
			throw new RuntimeException('no set via attribute found on property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
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

		$paramType = $param->getType();
		$propertyType = $property->getType();

		if (! $paramType instanceof ReflectionNamedType) {
			throw new RuntimeException('param type is ReflectionNamedType');
		}

		if (! $propertyType instanceof ReflectionNamedType) {
			throw new RuntimeException('property type is not ReflectionNamedType');
		}
		if ($paramType->getName() !== $propertyType->getName()) {
			throw new RuntimeException('only argument to setter method, must match type of property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		return new SetPropertyStrategy(
			method: SetPropertyStrategyMethod::SetterMethod,
			setterMethod: $method->name,
		);
	}

	public function extensionId(): string
	{
		return self::EXTENSION_NAME;
	}
}
