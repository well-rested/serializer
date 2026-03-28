<?php

declare(strict_types=1);

namespace WellRested\Serializer\Exceptions;

use Exception;
use Throwable;

class HoistTargetNotFoundException extends Exception
{
	public function __construct(
		string $container,
		string $path,
		string $toHoist,
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct(
			"property to hoist not found, parent: $container->$path, expected target: $toHoist",
			$code,
			$previous,
		);
	}
}
