<?php

declare(strict_types=1);

namespace Tests\Unit;

use Generator;
use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\AllPromoted;
use Tests\Unit\Analysis\Fixture\SimpleDummy;
use Tests\Unit\Analysis\Fixture\SnakeCaseNamingStrategy;
use Tests\Unit\Fixture\Arrays\ContainsArrayOfDummy;
use Tests\Unit\Fixture\Arrays\Dummy;
use Tests\Unit\Fixture\Arrays\ScalarArrays;
use Tests\Unit\Fixture\CastableTypes\CastableTypes;
use Tests\Unit\Fixture\Defaults\HasDefaults;
use Tests\Unit\Fixture\Nested\Child;
use Tests\Unit\Fixture\Nested\GrandChild;
use Tests\Unit\Fixture\Nested\Root;
use Tests\Unit\Fixture\NullableFields\WithNullableFields;
use Tests\Unit\Fixture\Options\OptionalFields;
use Tests\Unit\Fixture\Options\OptionalPromotedFields;
use Tests\Unit\Fixture\Options\SubField;
use Tests\Unit\Fixture\Serialization\Address;
use Tests\Unit\Fixture\Serialization\HoistableThing;
use Tests\Unit\Fixture\Serialization\HoistableThings;
use Tests\Unit\Fixture\Serialization\HoistsProperty;
use Tests\Unit\Fixture\Serialization\IdValueObject;
use Tests\Unit\Fixture\Serialization\User;
use WellRested\Serializer\Analysis\ClassAnalyser;
use WellRested\Serializer\Analysis\TypeDefinitions\Coercer;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionFactory;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Serializer;

#[CoversClass(Serializer::class)]
class SerializerTest extends TestCase
{
	protected ?Serializer $serializer;

	public function setUp(): void
	{
		parent::setUp();

		// TODO: mock these? kinda don't wanna mock the class analyser, this is more
		// of an integration/feature test really...
		$this->serializer = new Serializer(
			new ClassAnalyser(
				new SnakeCaseNamingStrategy(),
				new TypeDefinitionFactory(),
			),
			new Coercer(),
		);
	}

	protected function assertThatNoErrorsWereRaised(): void
	{
		$this->assertEquals(new FieldErrors(), $this->serializer->getRaisedErrors());
	}

	#[Group('openapi.serializer.deserialization')]
	public function testAllPromoted(): void
	{
		$value = $this->serializer->deserialize([
			'some_string' => 'blah',
			'some_int' => 1234,
			'some_bool' => true,
		], AllPromoted::class);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals(
			new AllPromoted(
				someString: 'blah',
				someBool: true,
				someInt: 1234,
			),
			$value->get(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testAllPromotedMissingSomeRequiredProperties(): void
	{
		$val = $this->serializer->deserialize([
			'some_int' => 1234,
		], AllPromoted::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'some_string',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'some_bool',
						message: 'value is required',
						value: None::create(),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testAllPromotedMissingAllRequiredProperties(): void
	{
		$val = $this->serializer->deserialize([], AllPromoted::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'some_string',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'some_int',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'some_bool',
						message: 'value is required',
						value: None::create(),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testAllPromotedIncorrectTypes(): void
	{
		$val = $this->serializer->deserialize([
			'some_string' => null,
			'some_int' => 'not an int', // not actually an int, even if it's numeric
			'some_bool' => [
				'i am not a bool',
			],
		], AllPromoted::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'some_string',
						message: 'invalid type, expected: string',
						value: new Some(null),
					),
				)
				->add(
					new FieldError(
						location: 'some_int',
						message: 'invalid type, expected: int',
						value: new Some('not an int'),
					),
				)
				->add(
					new FieldError(
						location: 'some_bool',
						message: 'invalid type, expected: bool',
						value: new Some([
							'i am not a bool',
						]),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testSimpleDummy(): void
	{
		$value = $this->serializer->deserialize([
			'some_int' => 4321,
			'some_string' => 'meh',
			'some_bool' => false,
			'some_float' => 12.1245,
			'some_array' => [
				'blah1',
				'blah2',
			],
			'some_null' => null,
			'some_mixed' => [
				'iAm' => 'a peacock',
				'you' => 'gotta let fly!',
			],
			'some_object' => [
				'ifI' => 'was a lion',
				'and' => 'you were a tuna',
			],
			'some_no_type' => [
				'some' => 'assoc',
			],
		], SimpleDummy::class);

		$expect = new SimpleDummy();
		$expect->someInt = 4321;
		$expect->someString = 'meh';
		$expect->someBool = false;
		$expect->someFloat = 12.1245;
		$expect->someArray = [
			'blah1',
			'blah2',
		];
		$expect->someNull = null;
		$expect->someMixed = [
			'iAm' => 'a peacock',
			'you' => 'gotta let fly!',
		];
		$expect->someObject = (function (): stdClass {
			$val = new stdClass();
			$val->ifI = 'was a lion';
			$val->and = 'you were a tuna';

			return $val;
		})();

		$expect->someNoType = [
			'some' => 'assoc',
		];

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals(
			$expect,
			$value->get(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testNullableFieldsMustBePresent(): void
	{
		$val = $this->serializer->deserialize([], WithNullableFields::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'name',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'id',
						message: 'value is required',
						value: None::create(),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testNullableFieldsMayBeNull(): void
	{
		$value = $this->serializer->deserialize([
			'name' => null,
			'id' => null,
		], WithNullableFields::class);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEqualsCanonicalizing(
			new WithNullableFields(
				id: null,
				name: null,
			),
			$value->get(),
		);
	}

	/**
	 * Some of the behaviours here are actually probably a little annoying. Should
	 * consider making this an opt-in functionality, per property (or maybe per class).
	 */
	#[DataProvider('castableTypesDataProvider')]
	#[Group('openapi.serializer.deserialization')]
	public function testCastableTypesValidCasts(array $data, CastableTypes $expect): void
	{
		$value = $this->serializer->deserialize($data, CastableTypes::class);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEqualsCanonicalizing(
			$expect,
			$value->get(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public static function castableTypesDataProvider(): Generator
	{
		// To string
		yield 'int to string' => [
			[
				'some_string' => 1234,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someString: '1234',
			),
		];

		yield 'float to string' => [
			[
				'some_string' => 11.234,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someString: '11.234',
			),
		];

		yield 'bool to string(true)' => [
			[
				'some_string' => true,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someString: 'true',
			),
		];

		yield 'bool to string(false)' => [
			[
				'some_string' => false,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someString: 'false',
			),
		];

		// To float
		yield 'int to float' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => 5363,
				'some_bool' => null,
			],
			new CastableTypes(
				someFloat: (float) 5363,
			),
		];

		yield 'string to float' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => '5363.63',
				'some_bool' => null,
			],
			new CastableTypes(
				someFloat: 5363.63,
			),
		];

		// To int
		yield 'string to int' => [
			[
				'some_string' => null,
				'some_int' => '1234',
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someInt: 1234,
			),
		];

		yield 'float to int(14.24)' => [
			[
				'some_string' => null,
				'some_int' => 14.24,
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someInt: 14,
			),
		];

		yield 'bool to int(1)' => [
			[
				'some_string' => null,
				'some_int' => true,
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someInt: 1,
			),
		];

		yield 'bool to int(0)' => [
			[
				'some_string' => null,
				'some_int' => false,
				'some_float' => null,
				'some_bool' => null,
			],
			new CastableTypes(
				someInt: 0,
			),
		];

		// To bool
		yield 'string to bool (yes)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'yes',
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'string to bool (YES)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'YES',
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'string to bool (y)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'y',
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'string to bool (Y)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'Y',
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'string to bool (TRUE)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'TRUE',
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'string to bool (true)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'true',
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'string to bool (no)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'no',
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'string to bool (NO)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'NO',
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'string to bool (n)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'n',
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'string to bool (N)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'N',
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'string to bool (FALSE)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'FALSe',
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'string to bool (false)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 'false',
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'int to bool (0)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 0,
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'int to bool (1)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 1,
			],
			new CastableTypes(
				someBool: true,
			),
		];

		// Not sure if it's common for negative numbers to be false...
		yield 'int to bool (-1)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => -1,
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'float to bool (1.2573)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 1.2573,
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'float to bool (0.5)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 0.5,
			],
			new CastableTypes(
				someBool: true,
			),
		];

		yield 'float to bool (0.499)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => 0.499,
			],
			new CastableTypes(
				someBool: false,
			),
		];

		yield 'float to bool (-1.537)' => [
			[
				'some_string' => null,
				'some_int' => null,
				'some_float' => null,
				'some_bool' => -1.537,
			],
			new CastableTypes(
				someBool: true,
			),
		];
	}

	#[Group('openapi.serializer.deserialization')]
	public function testNestedRoot(): void
	{
		$value = $this->serializer->deserialize([
			'id' => 'root-id',
			'my_number' => 5436,
			'reference' => 'my-reference',
			'child' => [
				'name' => 'child',
				'my_child' => [
					'name' => 'grandchild',
				],
			],
			'favourite_grand_child' => [
				'name' => 'i am favourite',
			],
		], Root::class);

		$expect = new Root(
			id: 'root-id',
			myNumber: 5436,
			reference: 'my-reference',
			child: new Child(
				name: 'child',
				myChild: new GrandChild(
					name: 'grandchild',
				),
			),
			favouriteGrandChild: new GrandChild(
				name: 'i am favourite',
			),
		);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals(
			$expect,
			$value->get(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testEmptyForNestedClass(): void
	{
		$val = $this->serializer->deserialize([
			'id' => 'root-id',
			'my_number' => 5436,
			'reference' => 'my-reference',
			'child' => [],
			'favourite_grand_child' => [
				'name' => 'i am favourite',
			],
		], Root::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'child.my_child',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'child.name',
						message: 'value is required',
						value: None::create(),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testDefaultsAreUsedIfNoValueSupplied(): void
	{
		$val = $this->serializer->deserialize([], HasDefaults::class);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEqualsCanonicalizing(
			new HasDefaults(),
			$val->get(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testNestedRootMissingSubproperty(): void
	{
		$val = $this->serializer->deserialize([
			'id' => 'root-id',
			'my_number' => 5436,
			'reference' => 'my-reference',
			'child' => [
				'name' => 'child',
			],
			'favourite_grand_child' => [
				'name' => 'i am favourite',
			],
		], Root::class);
		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'child.my_child',
						message: 'value is required',
						value: None::create(),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testNestedRootIncorrectTypeForSubproperty(): void
	{
		$val = $this->serializer->deserialize([
			'id' => 'root-id',
			'my_number' => 5436,
			'reference' => 'my-reference',
			'child' => [
				'name' => 'child',
				'my_child' => 'Dont objectify me!',
			],
			'favourite_grand_child' => [
				'name' => 'i am favourite',
			],
		], Root::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'child.my_child',
						message: 'invalid type, expected: ' . GrandChild::class,
						value: new Some('Dont objectify me!'),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testNestedRootEmptyArray(): void
	{
		$val = $this->serializer->deserialize([], Root::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'child',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'my_number',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'reference',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'favourite_grand_child',
						message: 'value is required',
						value: None::create(),
					),
				)
				->add(
					new FieldError(
						location: 'id',
						message: 'value is required',
						value: None::create(),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function arArrays()
	{
		$val = $this->serializer->deserialize([
			'ints' => [
				1, 5, 9, 12,
			],
			'strings' => [
				'blah',
				'meh',
			],
			'bools' => [
				true,
				false,
				true,
			],
			'mixed_values' => [
				'blah',
				['some', 'array'],
				1234,
			],
			'floats' => [
				1.23,
				6.543,
			],
		], ScalarArrays::class);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals(
			new ScalarArrays(
				ints: [
					1, 5, 9, 12,
				],
				strings: [
					'blah',
					'meh',
				],
				bools: [
					true,
					false,
					true,
				],
				mixedValues: [
					'blah',
					['some', 'array'],
					1234,
				],
				floats: [
					1.23,
					6.543,
				],
			),
			$val,
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testScalarArraysIncorrectTypeErrors()
	{
		// Most of these are coerced, so they're actually valid in most cases
		$val = $this->serializer->deserialize([
			'ints' => [
				1, 5, 'not a number', 12,
			],
			'strings' => [
				true,
			],
			'bools' => [
				1,
			],
			'mixed_values' => [
				'blah',
				['some', 'array'],
				1234,
				null,
			],
			'floats' => [
				1.23,
				123,
			],
		], ScalarArrays::class);

		$this->assertEquals(None::create(), $val);
		$this->assertEqualsCanonicalizing(
			(new FieldErrors())
				->add(
					new FieldError(
						location: 'ints.2',
						message: 'invalid type, expected: int',
						value: new Some(
							'not a number',
						),
					),
				),
			$this->serializer->getRaisedErrors(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testContainsArrayOfDummy()
	{
		$val = $this->serializer->deserialize([
			'dummies' => [
				[
					'id' => 1234,
					'name' => 'dummy_1',
				],
				[
					'id' => 4567,
					'name' => 'dummy_2',
				],
				[
					'id' => 8901,
					'name' => 'dummy_3',
				],
			],
		], ContainsArrayOfDummy::class);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals(
			new ContainsArrayOfDummy(
				[
					new Dummy(
						id: 1234,
						name: 'dummy_1',
					),
					new Dummy(
						id: 4567,
						name: 'dummy_2',
					),
					new Dummy(
						id: 8901,
						name: 'dummy_3',
					),
				],
			),
			$val->get(),
		);
	}

	#[Group('openapi.serializer.deserialization')]
	public function testOptionalSerializationNotPresent(): void
	{
		$val = $this->serializer->deserialize([], OptionalFields::class);

		$expect = new OptionalFields();
		$expect->email = None::create();
		$expect->firstName = None::create();
		$expect->lastName = None::create();
		$expect->sub = None::create();

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals($expect, $val->get());
	}

	#[Group('openapi.serializer.deserialization')]
	public function testOptionalSerializationPresent(): void
	{
		$val = $this->serializer->deserialize([], OptionalFields::class);

		$expect = new OptionalFields();
		$expect->email = None::create();
		$expect->firstName = None::create();
		$expect->lastName = None::create();
		$expect->sub = None::create();

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals($expect, $val->get());
	}

	#[Group('openapi.serializer.deserialization')]
	public function testOptionalSerializationPromotedNotPresent(): void
	{
		$val = $this->serializer->deserialize([
			'first_name' => 'Josephus',
			'last_name' => 'Miller',
			'sub' => [
				'line1' => 'blah',
			],
		], OptionalFields::class);

		$subField = new SubField();
		$subField->line1 = new Some('blah');
		$subField->line2 = None::create();

		$expect = new OptionalFields();
		$expect->email = None::create();
		$expect->firstName = new Some('Josephus');
		$expect->lastName = new Some('Miller');
		$expect->sub = new Some($subField);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals($expect, $val->get());
	}

	#[Group('openapi.serializer.deserialization')]
	public function testOptionalSerializationPromotedArePresent(): void
	{
		$val = $this->serializer->deserialize([
			'first_name' => 'Josephus',
			'last_name' => 'Miller',
			'sub' => [
				'line1' => 'blah',
				'line2' => 'meh',
			],
		], OptionalPromotedFields::class);

		$subField = new SubField();
		$subField->line1 = new Some('blah');
		$subField->line2 = new Some('meh');
		$expect = new OptionalPromotedFields(
			email: None::create(),
			firstName: new Some('Josephus'),
			lastName: new Some('Miller'),
			sub: new Some($subField),
		);

		$this->assertThatNoErrorsWereRaised();
		$this->assertEquals($expect, $val->get());
	}

	#[Group('openapi.serializer.serialization')]
	#[DataProvider('serializationOptionFieldsScenarios')]
	public function testSerializingOptionalFields(OptionalFields $input, array|stdClass $expect)
	{
		$val = $this->serializer->serialize($input);

		$this->assertEquals($expect, $val);
	}

	public static function serializationOptionFieldsScenarios(): Generator
	{
		$noneSet = new OptionalFields();
		$noneSet->email = None::create();
		$noneSet->firstName = None::create();
		$noneSet->lastName = None::create();
		$noneSet->sub = None::create();
		yield 'none are set' => [$noneSet, new stdClass()];

		$sub = new SubField();
		$sub->line1 = None::create();
		$sub->line2 = None::create();

		$someSet = new OptionalFields();
		$someSet->email = new Some('bobbie@mcrn.mrs');
		$someSet->firstName = None::create();
		$someSet->lastName = None::create();
		$someSet->sub = new Some($sub);

		yield 'some are set, sub object has none' => [$someSet, [
			'email' => 'bobbie@mcrn.mrs',
			'sub' => new stdClass(),
		]];

		$someSet = new OptionalFields();
		$someSet->email = new Some('bobbie@mcrn.mrs');
		$someSet->firstName = None::create();
		$someSet->lastName = None::create();
		$someSet->sub = None::create();

		yield 'some are set, sub object is none' => [$someSet, [
			'email' => 'bobbie@mcrn.mrs',
		]];
	}

	#[Group('openapi.serializer.serialization')]
	public function testSerializingUser()
	{
		$user = new User(
			firstName: 'Amos',
			lastName: 'Burton',
			age: 36,
			email: 'amos@thechurn.com',
			primaryAddress: new Address(
				line1: 'line1',
				line2: null,
				city: 'city',
				postCode: 'HU13 8HG',
			),
			secondaryAddresses: [
				new Address(
					line1: '2 - line1',
					line2: '2 - line2',
					city: '2 - city',
					postCode: '2 - HU13 8HG',
				),
				new Address(
					line1: '3 - line1',
					line2: null,
					city: '3 - city',
					postCode: '3 - HU13 8HG',
				),
			],
		);

		$val = $this->serializer->serialize($user);

		$this->assertEquals([
			'first_name' => 'Amos',
			'last_name' => 'Burton',
			'age' => 36,
			'email' => 'amos@thechurn.com',
			'primary_address' => [
				'line1' => 'line1',
				'line2' => null,
				'city' => 'city',
				'post_code' => 'HU13 8HG',
			],
			'secondary_addresses' => [
				[
					'line1' => '2 - line1',
					'line2' => '2 - line2',
					'city' => '2 - city',
					'post_code' => '2 - HU13 8HG',
				],
				[
					'line1' => '3 - line1',
					'line2' => null,
					'city' => '3 - city',
					'post_code' => '3 - HU13 8HG',
				],
			],
		], $val);
	}

	#[Group('openapi.serializer.serialization')]
	public function testSerializingHoistsProperty()
	{
		$subject = new HoistsProperty(
			id: new IdValueObject(
				id: 'myid',
			),
			hoistedThings: new HoistableThings([
				new HoistableThing(
					id: 'blah',
					enabled: false,
				),
				new HoistableThing(
					id: 'meh',
					enabled: true,
				),
			]),
			notHoistedThings: new HoistableThings([
				new HoistableThing(
					id: 'eshmeh',
					enabled: false,
				),
				new HoistableThing(
					id: 'blahblah',
					enabled: true,
				),
			]),
		);

		$val = $this->serializer->serialize($subject);

		$this->assertThatNoErrorsWereRaised();

		$this->assertEquals([
			'id' => 'myid',
			'hoisted_things' => [
				[
					'id' => 'blah',
					'enabled' => false,
				],
				[
					'id' => 'meh',
					'enabled' => true,
				],
			],
			'not_hoisted_things' => [
				'items' => [
					[
						'id' => 'eshmeh',
						'enabled' => false,
					],
					[
						'id' => 'blahblah',
						'enabled' => true,
					],
				],
			],
		], $val);
	}
}
