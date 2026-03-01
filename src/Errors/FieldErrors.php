<?php

declare(strict_types=1);

namespace WellRested\Serializer\Errors;

use WellRested\Serializer\Collection;

/**
 * @extends Collection<FieldError>
 */
class FieldErrors extends Collection
{
	protected static function isType(mixed $value): bool
	{
		return $value instanceof FieldError;
	}
}
