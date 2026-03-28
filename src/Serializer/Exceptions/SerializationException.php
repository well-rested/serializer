<?php

declare(strict_types=1);

namespace WellRested\Serializer\Exceptions;

use Exception;
use Throwable;
use WellRested\Serializer\Errors\FieldErrors;

class SerializationException extends Exception
{
	public function __construct(
		protected object $subject,
		protected FieldErrors $errors,
		string $message = "serialization failed",
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct(
			$message,
			$code,
			$previous,
		);
	}

	public function getSubject(): object
	{
		return $this->subject;
	}

	public function getErrors(): FieldErrors
	{
		return $this->errors;
	}
}
