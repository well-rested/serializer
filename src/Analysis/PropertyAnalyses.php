<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use WellRested\Serializer\Dictionary;

/**
 * @extends Dictionary<PropertyAnalysis>
 */
class PropertyAnalyses extends Dictionary
{
	protected static function isType(mixed $value): bool
	{
		return $value instanceof PropertyAnalysis;
	}
}
