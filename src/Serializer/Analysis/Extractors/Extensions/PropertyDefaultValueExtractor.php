<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use WellRested\Serializer\Util\MixedDictionary;

class PropertyDefaultValueExtractor implements ExtendsPropertyExtraction
{
	public const EXTENSION_NAME = "builtin.default_values_extractor";

	public function extract(ReflectionProperty $property): MixedDictionary
	{
		$hasDefault = $property->hasDefaultValue();
		$defaultValue = $property->getDefaultValue();

		if ($property->isPromoted()) {
			$defaultValue = null;
			$constructorMethod = $property->getDeclaringClass()->getConstructor();

			if ($constructorMethod === null) {
				throw new RuntimeException('null constructor method');
			}

			/** @var ReflectionParameter[] $constructorParams */
			$constructorParams = $constructorMethod->getParameters();

			foreach ($constructorParams as $param) {
				if ($param->getName() == $property->getName()) {
					$hasDefault = $param->isDefaultValueAvailable();

					if ($hasDefault) {
						$defaultValue = $param->getDefaultValue();
					}
				}
			}
		}

		$dict = new MixedDictionary();

		$dict->add('value', $defaultValue);
		$dict->add('default_exists', $hasDefault);

		return $dict;
	}

	public function extensionId(): string
	{
		return self::EXTENSION_NAME;
	}
}
