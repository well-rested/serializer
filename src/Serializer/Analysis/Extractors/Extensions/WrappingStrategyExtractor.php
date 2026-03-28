<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionProperty;
use WellRested\Serializer\Analysis\WrappingStrategy;
use WellRested\Serializer\Attributes\Wrap;
use WellRested\Serializer\Util\MixedDictionary;

class WrappingStrategyExtractor implements ExtendsPropertyExtraction
{
	public const EXTENSION_NAME = "builtin.wrapping_strategy_extractor";

	public function extract(ReflectionProperty $property): MixedDictionary
	{
		$attr = $property->getAttributes(Wrap::class)[0] ?? null;

		if ($attr === null) {
			return (new MixedDictionary())->add('value', new WrappingStrategy(
				enabled: false,
			));
		}

		$instance = $attr->newInstance();

		return (new MixedDictionary())->add('value', new WrappingStrategy(
			enabled: true,
			key: $instance->key,
		));
	}

	public function extensionId(): string
	{
		return self::EXTENSION_NAME;
	}
}
