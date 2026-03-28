<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionProperty;
use RuntimeException;
use Symfony\Component\TypeInfo\Type\ObjectType;
use WellRested\Serializer\Analysis\HoistStrategy;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Attributes\Hoist;
use WellRested\Serializer\Util\MixedDictionary;

class HoistStrategyExtractor implements ExtendsPropertyExtraction
{
	public const EXTENSION_NAME = "builtin.hoist_strategy_extractor";

	public function __construct(
		protected Reflector $reflector,
	) {}

	public function extract(ReflectionProperty $property): MixedDictionary
	{
		$dict = new MixedDictionary();

		$attr = $property->getAttributes(Hoist::class)[0] ?? null;

		$noHoistStrategy = new HoistStrategy(
			enabled: false,
		);

		if ($attr === null) {
			return $dict->add('value', $noHoistStrategy);
		}

		$instance = $attr->newInstance();

		$type = $this->reflector->getPropertyType($property->getDeclaringClass()->getName(), $instance->property);

		if (! $type instanceof ObjectType) {
			throw new RuntimeException('cannot hoist a property from a non-object in: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
		}

		return $dict->add('value', $this->handleObjectType($instance, $type));
	}

	/**
	 * @param ObjectType<mixed> $type
	 */
	protected function handleObjectType(Hoist $attrInstance, ObjectType $type): HoistStrategy
	{
		return new HoistStrategy(
			enabled: true,
			property: $attrInstance->property,
		);
	}

	public function extensionId(): string
	{
		return self::EXTENSION_NAME;
	}
}
