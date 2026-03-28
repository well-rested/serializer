<?php

declare(strict_types=1);

namespace WellRested\Serializer\Util;

/**
 * @extends Dictionary<mixed>
 */
class MixedDictionary extends Dictionary
{
	protected static function isType(mixed $value): bool
	{
		return true;
	}
}
