<?php

declare(strict_types=1);

namespace WellRested\Serializer\Exceptions;

use Exception;
use Throwable;

class IncorrectTypeForHoistException extends Exception
{
	public function __construct(
		string $container,
		string $path,
		string $type,
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct(
			"property with hoist attribute was incorrect type given: $type, for $container->$path",
			$code,
			$previous,
		);
	}
}
