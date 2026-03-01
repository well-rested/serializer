<?php

declare(strict_types=1);

namespace WellRested\Serializer\Errors;

use WellRested\Serializer\Collection;

class FieldErrors extends Collection
{
	protected static function isType(mixed $value): bool
	{
		return $value instanceof FieldError;
	}
}
