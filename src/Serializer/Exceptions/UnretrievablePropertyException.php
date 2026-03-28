<?php

declare(strict_types=1);

namespace WellRested\Serializer\Exceptions;

use Exception;
use Throwable;

class UnretrievablePropertyException extends Exception
{
	public function __construct(
		string $container,
		string $path,
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct(
			"property could not be retrieved: $container->$path",
			$code,
			$previous,
		);
	}
}
