<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Normalizers\Traits;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Analysis\Types\OptionType;
use WellRested\Serializer\Normalizers\Traits\ValidatesValueTypes;

#[CoversTrait(ValidatesValueTypes::class)]
class ValidatesValueTypesTest extends TestCase
{
	private function makeSubject(): object
	{
		return new class {
			use ValidatesValueTypes;

			public function check(mixed $value, mixed $type): bool
			{
				return $this->valueIsCompatibleWithType($value, $type);
			}
		};
	}

	// --- valueIsCompatibleWithType ---

	public function test_option_type_unwraps_and_checks_inner_type(): void
	{
		$subject = $this->makeSubject();

		$this->assertTrue($subject->check(42, new OptionType(new BuiltinType(TypeIdentifier::INT))));
		$this->assertFalse($subject->check('nope', new OptionType(new BuiltinType(TypeIdentifier::ARRAY))));
	}

	public function test_nullable_type_with_null_returns_true(): void
	{
		$subject = $this->makeSubject();

		$this->assertTrue($subject->check(null, new NullableType(new BuiltinType(TypeIdentifier::INT))));
	}

	public function test_object_type_with_array_returns_true(): void
	{
		$subject = $this->makeSubject();

		$this->assertTrue($subject->check(['key' => 'val'], new ObjectType(stdClass::class)));
	}

	public function test_object_type_with_stdclass_returns_true(): void
	{
		$subject = $this->makeSubject();

		$this->assertTrue($subject->check(new stdClass(), new ObjectType(stdClass::class)));
	}

	public function test_object_type_with_unrecognised_value_throws(): void
	{
		$subject = $this->makeSubject();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('cannot check compatible type');

		$subject->check('not-an-object', new ObjectType(stdClass::class));
	}

	public function test_union_type_returns_true_when_any_member_matches(): void
	{
		$subject = $this->makeSubject();
		$type = new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::ARRAY));

		$this->assertTrue($subject->check([], $type));
	}

	public function test_union_type_returns_false_when_no_member_matches(): void
	{
		$subject = $this->makeSubject();
		$type = new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::ARRAY));

		$this->assertFalse($subject->check(new stdClass(), $type));
	}

	public function test_unrecognised_type_throws(): void
	{
		$subject = $this->makeSubject();

		// GenericType alone is not handled by any branch
		$type = new GenericType(new BuiltinType(TypeIdentifier::ARRAY), new BuiltinType(TypeIdentifier::STRING));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('cannot check compatible type');

		$subject->check([], $type);
	}

	// --- valueIsCompatibleWithBuiltinType ---

	/** @return array<string, array{mixed, TypeIdentifier, bool}> */
	public static function builtinTypeProvider(): array
	{
		return [
			'mixed accepts anything'          => [new stdClass(), TypeIdentifier::MIXED, true],
			'null accepts null'               => [null, TypeIdentifier::NULL, true],
			'null rejects non-null'           => ['foo', TypeIdentifier::NULL, false],
			'array accepts array'             => [[], TypeIdentifier::ARRAY, true],
			'array rejects string'            => ['foo', TypeIdentifier::ARRAY, false],
			'int accepts int'                 => [1, TypeIdentifier::INT, true],
			'int accepts bool'                => [true, TypeIdentifier::INT, true],
			'int accepts float'               => [1.5, TypeIdentifier::INT, true],
			'int accepts numeric string'      => ['42', TypeIdentifier::INT, true],
			'int rejects non-numeric string'  => ['abc', TypeIdentifier::INT, false],
			'float accepts float'             => [1.5, TypeIdentifier::FLOAT, true],
			'float accepts int'               => [1, TypeIdentifier::FLOAT, true],
			'float accepts numeric string'    => ['3.14', TypeIdentifier::FLOAT, true],
			'float rejects non-numeric string' => ['abc', TypeIdentifier::FLOAT, false],
			'string accepts string'           => ['hello', TypeIdentifier::STRING, true],
			'string accepts int'              => [42, TypeIdentifier::STRING, true],
			'string accepts bool'             => [false, TypeIdentifier::STRING, true],
			'string rejects array'            => [[], TypeIdentifier::STRING, false],
			'bool accepts bool'               => [true, TypeIdentifier::BOOL, true],
			'bool accepts int'                => [1, TypeIdentifier::BOOL, true],
			'bool accepts yes string'         => ['yes', TypeIdentifier::BOOL, true],
			'bool accepts no string'          => ['no', TypeIdentifier::BOOL, true],
			'bool rejects arbitrary string'   => ['maybe', TypeIdentifier::BOOL, false],
			'object accepts array'            => [[], TypeIdentifier::OBJECT, true],
			'object accepts stdClass'         => [new stdClass(), TypeIdentifier::OBJECT, true],
			'object rejects string'           => ['foo', TypeIdentifier::OBJECT, false],
			'default identifier returns false' => [null, TypeIdentifier::VOID, false],
		];
	}

	#[DataProvider('builtinTypeProvider')]
	public function test_builtin_type_compatibility(mixed $value, TypeIdentifier $identifier, bool $expected): void
	{
		$subject = $this->makeSubject();

		$this->assertSame($expected, $subject->check($value, new BuiltinType($identifier)));
	}

	// --- valueIsCompatibleWithCollectionType ---

	private function intKeyStringValueCollection(): CollectionType
	{
		return new CollectionType(
			new GenericType(
				new BuiltinType(TypeIdentifier::ARRAY),
				new BuiltinType(TypeIdentifier::INT),
				new BuiltinType(TypeIdentifier::STRING),
			),
		);
	}

	public function test_collection_rejects_non_array(): void
	{
		$subject = $this->makeSubject();

		$this->assertFalse($subject->check('not-an-array', $this->intKeyStringValueCollection()));
	}

	public function test_collection_accepts_empty_array(): void
	{
		$subject = $this->makeSubject();

		$this->assertTrue($subject->check([], $this->intKeyStringValueCollection()));
	}

	public function test_collection_accepts_valid_typed_array(): void
	{
		$subject = $this->makeSubject();

		$this->assertTrue($subject->check([0 => 'hello', 1 => 'world'], $this->intKeyStringValueCollection()));
	}

	public function test_collection_rejects_invalid_value_type(): void
	{
		$subject = $this->makeSubject();

		// arrays cannot be coerced to string, so this value is incompatible
		$this->assertFalse($subject->check([0 => []], $this->intKeyStringValueCollection()));
	}

	public function test_collection_rejects_invalid_key_type(): void
	{
		$subject = $this->makeSubject();

		// string keys against an int-keyed collection
		$this->assertFalse($subject->check(['bad-key' => 'hello'], $this->intKeyStringValueCollection()));
	}
}
