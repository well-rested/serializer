<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionProperty;
use WellRested\Serializer\Attributes\Field;
use WellRested\Serializer\Util\MixedDictionary;
use WellRested\Serializer\Naming\NamingStrategyInterface;

class SerializedPropertyNameExtractor implements ExtendsPropertyExtraction
{
	public const EXTENSION_NAME = "builtin.serialized_property_name_extractor";

	public function __construct(
		protected NamingStrategyInterface $namingStrategy,
	) {}

	public function extract(ReflectionProperty $property): MixedDictionary
	{
		$attr = $property->getAttributes(Field::class)[0] ?? null;

		$field = null === $attr ? null : $attr->newInstance();

		$name = $field->name ?? $this->namingStrategy->convert($property->getName());

		$dict = new MixedDictionary();

		$dict->add('value', $name);

		return $dict;
	}

	public function extensionId(): string
	{
		return self::EXTENSION_NAME;
	}
}
