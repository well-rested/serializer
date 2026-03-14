<?php

declare(strict_types=1);

namespace Tests\Unit\Errors;

use InvalidArgumentException;
use PhpOption\None;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;

#[CoversClass(FieldErrors::class)]
class FieldErrorsTest extends TestCase
{
	public function test_accepts_field_error_instances(): void
	{
		$errors = new FieldErrors();
		$error = new FieldError('field', FieldErrorType::ValueIsRequired, None::create());

		$errors->add($error);

		$this->assertSame([$error], $errors->all());
	}

	public function test_rejects_non_field_error_values(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$errors = new FieldErrors();
		$errors->add('not a FieldError'); // @phpstan-ignore-line
	}
}
