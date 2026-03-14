<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Exceptions\DeserializationException;

#[CoversClass(DeserializationException::class)]
class DeserializationExceptionTest extends TestCase
{
	public function test_extends_exception(): void
	{
		$e = new DeserializationException(stdClass::class, [], new FieldErrors());

		$this->assertInstanceOf(Exception::class, $e);
	}

	public function test_default_message(): void
	{
		$e = new DeserializationException(stdClass::class, [], new FieldErrors());

		$this->assertSame('deserialization failed', $e->getMessage());
	}

	public function test_custom_message(): void
	{
		$e = new DeserializationException(stdClass::class, [], new FieldErrors(), 'something went wrong');

		$this->assertSame('something went wrong', $e->getMessage());
	}

	public function test_get_target_class(): void
	{
		$e = new DeserializationException(stdClass::class, [], new FieldErrors());

		$this->assertSame(stdClass::class, $e->getTargetClass());
	}

	public function test_get_data(): void
	{
		$data = ['foo' => 'bar'];
		$e = new DeserializationException(stdClass::class, $data, new FieldErrors());

		$this->assertSame($data, $e->getData());
	}

	public function test_get_errors(): void
	{
		$errors = new FieldErrors();
		$e = new DeserializationException(stdClass::class, [], $errors);

		$this->assertSame($errors, $e->getErrors());
	}

	public function test_code_is_forwarded(): void
	{
		$e = new DeserializationException(stdClass::class, [], new FieldErrors(), 'msg', 42);

		$this->assertSame(42, $e->getCode());
	}

	public function test_previous_is_forwarded(): void
	{
		$previous = new RuntimeException('root cause');
		$e = new DeserializationException(stdClass::class, [], new FieldErrors(), 'msg', 0, $previous);

		$this->assertSame($previous, $e->getPrevious());
	}
}
