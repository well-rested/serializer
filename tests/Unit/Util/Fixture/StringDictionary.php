<?php

declare(strict_types=1);

namespace Tests\Unit\Util\Fixture;

use WellRested\Serializer\Util\Dictionary;

/**
 * @extends Dictionary<string>
 */
class StringDictionary extends Dictionary
{
	protected static function isType(mixed $value): bool
	{
		return is_string($value);
	}
}
