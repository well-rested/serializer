<?php

declare(strict_types=1);

namespace WellRested\Serializer\Exceptions;

use Exception;
use Throwable;
use WellRested\Serializer\Errors\FieldErrors;

class DeserializationException extends Exception
{
	/**
	 * @param class-string $targetClass
	 */
	public function __construct(
		protected string $targetClass,
		protected mixed $data,
		protected FieldErrors $errors,
		string $message = "deserialization failed",
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct(
			$message,
			$code,
			$previous,
		);
	}

	public function getErrors(): FieldErrors
	{
		return $this->errors;
	}

	public function getData(): mixed
	{
		return $this->data;
	}

	/**
	 * @return class-string
	 */
	public function getTargetClass(): string
	{
		return $this->targetClass;
	}
}
