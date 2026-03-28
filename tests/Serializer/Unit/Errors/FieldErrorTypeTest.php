<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Errors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WellRested\Serializer\Errors\FieldErrorType;

#[CoversClass(FieldErrorType::class)]
class FieldErrorTypeTest extends TestCase
{
	public function test_value_is_required_backing_value(): void
	{
		$this->assertSame('value_is_required', FieldErrorType::ValueIsRequired->value);
	}

	public function test_value_is_invalid_type_backing_value(): void
	{
		$this->assertSame('value_is_invalid_type', FieldErrorType::ValueIsInvalidType->value);
	}

	public function test_invalid_collection_key_type_backing_value(): void
	{
		$this->assertSame('invalid_collection_key_type', FieldErrorType::InvalidCollectionKeyType->value);
	}

	public function test_unsatisfiable_union_type_backing_value(): void
	{
		$this->assertSame('unsatisfiable_union_type', FieldErrorType::UnsatisfiableUnionType->value);
	}
}
