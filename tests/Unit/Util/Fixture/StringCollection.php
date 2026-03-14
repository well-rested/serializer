<?php

declare(strict_types=1);

namespace Tests\Unit\Util\Fixture;

use WellRested\Serializer\Util\Collection;

/**
 * @extends Collection<string>
 */
class StringCollection extends Collection
{
	protected static function isType(mixed $value): bool
	{
		return is_string($value);
	}
}
