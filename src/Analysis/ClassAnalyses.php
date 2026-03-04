<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use WellRested\Serializer\Dictionary;

/**
 * @extends Dictionary<ClassAnalysis>
 */
class ClassAnalyses extends Dictionary
{
	protected static function isType(mixed $value): bool
	{
		return $value instanceof ClassAnalysis;
	}
}
