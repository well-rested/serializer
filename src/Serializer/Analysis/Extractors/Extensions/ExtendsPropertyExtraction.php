<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionProperty;
use WellRested\Serializer\Util\MixedDictionary;

interface ExtendsPropertyExtraction
{
	public function extract(ReflectionProperty $property): MixedDictionary;

	public function extensionId(): string;
}
