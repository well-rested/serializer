<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionProperty;
use RuntimeException;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Attributes\GetVia;
use WellRested\Serializer\Util\MixedDictionary;

class PropertyGetterMethodExtractor implements ExtendsPropertyExtraction
{
	public const EXTENSION_NAME = "builtin.property_getter_method_extractor";

	public function extract(ReflectionProperty $property): MixedDictionary
	{
		$dict = new MixedDictionary();

		$strategy = match (true) {
			$property->isPublic() => new GetPropertyStrategy(
				method: GetPropertyStrategyMethod::PublicGetter,
			),
			($property->getAttributes(GetVia::class)[0] ?? null) !== null => $this->handleGetViaAttribute($property),
			default => throw new RuntimeException('could not find way to get property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName()),
		};

		$dict->add('value', $strategy);

		return $dict;
	}

	protected function handleGetViaAttribute(ReflectionProperty $property): GetPropertyStrategy
	{
		$getViaReflAttr = $property->getAttributes(GetVia::class)[0] ?? null;

		if (null === $getViaReflAttr) {
			throw new RuntimeException('get via attribute not found on property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
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

		if ((string) $method->getReturnType() !== (string) $property->getType()) {
			throw new RuntimeException('return type from getter method, must match type of property: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		return new GetPropertyStrategy(
			method: GetPropertyStrategyMethod::GetterMethod,
			getterMethod: $method->name,
		);
	}

	public function extensionId(): string
	{
		return self::EXTENSION_NAME;
	}
}
