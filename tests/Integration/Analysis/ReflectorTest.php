<?php

declare(strict_types=1);

namespace Tests\Integration\Analysis;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tests\Integration\Analysis\Fixture\Reflector\PropertyTypes;
use Tests\Integration\Analysis\Fixture\Reflector\SimpleDummy;
use WellRested\Serializer\Analysis\Reflector;

class ReflectorTest extends TestCase
{
	protected ?Reflector $subject;

	public function setUp(): void
	{
		$this->subject = new Reflector();
	}

	#[DataProvider('scenarios')]
	public function test_expectations(string $propertyName, Type $expect): void
	{
		$this->assertEquals(
			$expect,
			$this->subject->getPropertyType(PropertyTypes::class, $propertyName),
		);
	}

	public static function scenarios(): Generator
	{
		// Types from php typehints
		yield 'someInt' => [
			'someInt',
			new BuiltinType(TypeIdentifier::INT),
		];

		yield 'someString' => [
			'someString',
			new BuiltinType(TypeIdentifier::STRING),
		];

		yield 'someBool' => [
			'someBool',
			new BuiltinType(TypeIdentifier::BOOL),
		];

		yield 'someFloat' => [
			'someFloat',
			new BuiltinType(TypeIdentifier::FLOAT),
		];

		yield 'someMixed' => [
			'someMixed',
			new BuiltinType(TypeIdentifier::MIXED),
		];

		yield 'someObject' => [
			'someObject',
			new BuiltinType(TypeIdentifier::OBJECT),
		];

		yield 'simpleDummy' => [
			'simpleDummy',
			new ObjectType(
				className: SimpleDummy::class,
			),
		];

		yield 'nullableString' => [
			'nullableString',
			new NullableType(
				new BuiltinType(TypeIdentifier::STRING),
			),
		];

		yield 'nullableSimpleDummy' => [
			'nullableSimpleDummy',
			new NullableType(
				new ObjectType(
					className: SimpleDummy::class,
				),
			),
		];

		yield 'stringOrInt' => [
			'stringOrInt',
			new UnionType(
				new BuiltinType(TypeIdentifier::STRING),
				new BuiltinType(TypeIdentifier::INT),
			),
		];

		yield 'simpleDummyOrInt' => [
			'simpleDummyOrInt',
			new UnionType(
				new ObjectType(
					className: SimpleDummy::class,
				),
				new BuiltinType(TypeIdentifier::INT),
			),
		];

		yield 'nullableSimpleDummyOrInt' => [
			'nullableSimpleDummyOrInt',
			new NullableType(
				new UnionType(
					new ObjectType(
						className: SimpleDummy::class,
					),
					new BuiltinType(TypeIdentifier::INT),
				),
			),
		];

		// Types from docblocks — docblock takes priority over the php typehint

		// typehint: int, docblock: float
		yield 'intWithDocblock' => [
			'intWithDocblock',
			new BuiltinType(TypeIdentifier::FLOAT),
		];

		// typehint: int, docblock: string|null
		yield 'intDocblockedToNullableString' => [
			'intDocblockedToNullableString',
			new NullableType(
				new BuiltinType(TypeIdentifier::STRING),
			),
		];

		// typehint: mixed, docblock: SimpleDummy
		yield 'mixedDocblockedToObject' => [
			'mixedDocblockedToObject',
			new ObjectType(
				className: SimpleDummy::class,
			),
		];

		// typehint: array, docblock: array<string|int, mixed>
		yield 'typeNarrowedArray1' => [
			'typeNarrowedArray1',
			new CollectionType(
				new GenericType(
					new BuiltinType(TypeIdentifier::ARRAY),
					new UnionType(
						new BuiltinType(TypeIdentifier::STRING),
						new BuiltinType(TypeIdentifier::INT),
					),
					new BuiltinType(TypeIdentifier::MIXED),
				),
			),
		];

		// typehint: array, docblock: array<int, SimpleDummy>
		yield 'objectValuedArray' => [
			'objectValuedArray',
			new CollectionType(
				new GenericType(
					new BuiltinType(TypeIdentifier::ARRAY),
					new BuiltinType(TypeIdentifier::INT),
					new ObjectType(className: SimpleDummy::class),
				),
			),
		];

		// typehint: array, docblock: string[]
		yield 'stringListArray' => [
			'stringListArray',
			new CollectionType(
				new GenericType(
					new BuiltinType(TypeIdentifier::ARRAY),
					new BuiltinType(TypeIdentifier::INT),
					new BuiltinType(TypeIdentifier::STRING),
				),
				isList: true,
			),
		];
	}
}
