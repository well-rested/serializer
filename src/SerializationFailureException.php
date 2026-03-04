<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use Exception;
use WellRested\Serializer\Errors\FieldErrors;
use Throwable;

class SerializationFailureException extends Exception
{
	public function __construct(
		protected FieldErrors $fieldErrors,
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct($message, $code, $previous);
	}

	public function getErrors(): FieldErrors
	{
		return $this->fieldErrors;
	}
}
