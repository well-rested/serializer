<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Exceptions\SerializationException;

#[CoversClass(SerializationException::class)]
class SerializationExceptionTest extends TestCase
{
	public function test_extends_exception(): void
	{
		$e = new SerializationException(new stdClass(), new FieldErrors());

		$this->assertInstanceOf(Exception::class, $e);
	}

	public function test_default_message(): void
	{
		$e = new SerializationException(new stdClass(), new FieldErrors());

		$this->assertSame('serialization failed', $e->getMessage());
	}

	public function test_custom_message(): void
	{
		$e = new SerializationException(new stdClass(), new FieldErrors(), 'something went wrong');

		$this->assertSame('something went wrong', $e->getMessage());
	}

	public function test_get_subject(): void
	{
		$subject = new stdClass();
		$e = new SerializationException($subject, new FieldErrors());

		$this->assertSame($subject, $e->getSubject());
	}

	public function test_get_errors(): void
	{
		$errors = new FieldErrors();
		$e = new SerializationException(new stdClass(), $errors);

		$this->assertSame($errors, $e->getErrors());
	}

	public function test_code_is_forwarded(): void
	{
		$e = new SerializationException(new stdClass(), new FieldErrors(), 'msg', 42);

		$this->assertSame(42, $e->getCode());
	}

	public function test_previous_is_forwarded(): void
	{
		$previous = new RuntimeException('root cause');
		$e = new SerializationException(new stdClass(), new FieldErrors(), 'msg', 0, $previous);

		$this->assertSame($previous, $e->getPrevious());
	}
}
