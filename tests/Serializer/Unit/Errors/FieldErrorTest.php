<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Errors;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrorType;

#[CoversClass(FieldError::class)]
class FieldErrorTest extends TestCase
{
	public function test_location_is_set(): void
	{
		$error = new FieldError('some.field', FieldErrorType::ValueIsRequired, None::create());

		$this->assertSame('some.field', $error->location);
	}

	public function test_type_is_set(): void
	{
		$error = new FieldError('field', FieldErrorType::ValueIsInvalidType, None::create());

		$this->assertSame(FieldErrorType::ValueIsInvalidType, $error->type);
	}

	public function test_value_is_set(): void
	{
		$option = Some::create('hello');
		$error = new FieldError('field', FieldErrorType::ValueIsRequired, $option);

		$this->assertSame($option, $error->value);
	}

	public function test_to_array_without_value(): void
	{
		$error = new FieldError('some.field', FieldErrorType::ValueIsRequired, None::create());

		$this->assertSame(
			['location' => 'some.field', 'message' => 'value_is_required'],
			$error->toArray(),
		);
	}

	public function test_to_array_omits_value_key_when_none(): void
	{
		$error = new FieldError('field', FieldErrorType::ValueIsRequired, None::create());

		$this->assertArrayNotHasKey('value', $error->toArray());
	}

	/** @return array<string, array{mixed, mixed}> */
	public static function valueRoundTrips(): array
	{
		$obj = new stdClass();
		$obj->key = 'val';

		return [
			'string'           => ['hello', 'hello'],
			'integer'          => [42, 42],
			'float'            => [1.5, 1.5],
			'null'             => [null, null],
			'indexed array'    => [['a', 'b'], ['a', 'b']],
			'associative array' => [['key' => 'val'], $obj],
		];
	}

	#[DataProvider('valueRoundTrips')]
	public function test_to_array_includes_json_decoded_value(mixed $input, mixed $expected): void
	{
		$error = new FieldError('field', FieldErrorType::ValueIsInvalidType, Some::create($input));

		$result = $error->toArray();

		$this->assertArrayHasKey('value', $result);
		$this->assertEquals($expected, $result['value']);
	}

	public function test_to_array_uses_error_type_as_message(): void
	{
		foreach (FieldErrorType::cases() as $type) {
			$error = new FieldError('f', $type, None::create());
			$this->assertSame($type->value, $error->toArray()['message']);
		}
	}
}
