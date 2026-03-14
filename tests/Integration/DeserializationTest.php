<?php

declare(strict_types=1);

namespace Tests\Integration;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Integration\Fixture\AllStandardTypesWithPublicSetters;
use Tests\Integration\Fixture\DefaultValues;
use Tests\Integration\Fixture\Hierarchy\Btm;
use Tests\Integration\Fixture\Hierarchy\Mid;
use Tests\Integration\Fixture\Hierarchy\Top;
use Tests\Integration\Fixture\NullableFields;
use Tests\Integration\Fixture\OptionalPromotedProperties;
use Tests\Integration\Fixture\PublicPromotedProperties;
use Tests\Integration\Fixture\Union\Container;
use Tests\Integration\Fixture\Union\TypeA;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysisExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\HoistStrategyExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyDefaultValueExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyGetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertySetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\SerializedPropertyNameExtractor;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysisExtractor;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Exceptions\DeserializationException;
use WellRested\Serializer\Naming\SnakeCaseNamingStrategy;
use WellRested\Serializer\Normalizers\CollectionNormalizer;
use WellRested\Serializer\Normalizers\GenericNormalizer;
use WellRested\Serializer\Normalizers\ObjectNormalizer;
use WellRested\Serializer\Normalizers\OptionNormalizer;
use WellRested\Serializer\Normalizers\UnionNormalizer;
use WellRested\Serializer\Serializer;

class DeserializationTest extends TestCase
{
	protected ?Serializer $serializer;

	public function setUp(): void
	{
		parent::setUp();

		$extractor = new ClassAnalysisExtractor(
			new PropertyAnalysisExtractor(
				reflector: new Reflector(),
				extensions: [
					new PropertyDefaultValueExtractor(),
					new SerializedPropertyNameExtractor(
						new SnakeCaseNamingStrategy(),
					),
					new PropertySetterMethodExtractor(),
					new PropertyGetterMethodExtractor(),
					new HoistStrategyExtractor(
						reflector: new Reflector(),
					),
				],
			),
		);

		$this->serializer = new Serializer(
			normalizers: [
				new OptionNormalizer(),
				new UnionNormalizer(),
				new ObjectNormalizer($extractor),
				new CollectionNormalizer(),
				new GenericNormalizer(),
			],
		);
	}

	protected function assertDeserializationException(callable $toCall, string $targetClass, array $data, FieldErrors $fieldErrors): void
	{
		try {
			$toCall();
		} catch (DeserializationException $e) {
			$this->assertEqualsCanonicalizing($fieldErrors, $e->getErrors());
			$this->assertEquals($data, $e->getData());
			$this->assertEquals($targetClass, $e->getTargetClass());
			return;
		}

		$this->fail('expected DeserializationException, got none');
	}

	protected function assertThatNoErrorsWereRaised(): void
	{
		$this->assertEquals(new FieldErrors(), $this->serializer->getRaisedErrors(), "errors found: " . print_r($this->serializer->getRaisedErrors()->all(), true));
	}

	#[Group('serializer.deserialization')]
	public function test_public_promoted_properties(): void
	{
		$value = $this->serializer->deserialize([
			'some_string' => 'blah',
			'some_int' => 1234,
			'some_bool' => true,
		], PublicPromotedProperties::class);

		$this->assertEquals(
			new PublicPromotedProperties(
				someString: 'blah',
				someBool: true,
				someInt: 1234,
			),
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_public_promoted_properties_missing_fields(): void
	{
		$data = [];

		$this->assertDeserializationException(
			fn() => $this->serializer->deserialize($data, PublicPromotedProperties::class),
			PublicPromotedProperties::class,
			$data,
			(new FieldErrors())
				->add(new FieldError('some_int', FieldErrorType::ValueIsRequired, None::create()))
				->add(new FieldError('some_string', FieldErrorType::ValueIsRequired, None::create()))
				->add(new FieldError('some_bool', FieldErrorType::ValueIsRequired, None::create())),
		);
	}

	#[Group('serializer.deserialization')]
	public function test_public_promoted_properties_invalid_types(): void
	{
		$data = [
			'some_int' => 'im not an int brah!',
			'some_bool' => 'not a bool either',
			'some_string' => [],
		];

		$this->assertDeserializationException(
			fn() => $this->serializer->deserialize($data, PublicPromotedProperties::class),
			PublicPromotedProperties::class,
			$data,
			(new FieldErrors())
				->add(new FieldError('some_int', FieldErrorType::ValueIsInvalidType, Some::create('im not an int brah!')))
				->add(new FieldError('some_string', FieldErrorType::ValueIsInvalidType, Some::create([])))
				->add(new FieldError('some_bool', FieldErrorType::ValueIsInvalidType, Some::create('not a bool either'))),
		);
	}

	#[Group('serializer.deserialization')]
	public function test_optional_promoted_properties(): void
	{
		$value = $this->serializer->deserialize([
			'some_string' => 'blah',
			'some_int' => 1234,
			'some_bool' => true,
		], OptionalPromotedProperties::class);

		$this->assertEquals(
			new OptionalPromotedProperties(
				someString: Some::create('blah'),
				someBool: Some::create(true),
				someInt: Some::create(1234),
			),
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_optional_promoted_properties_missing_all(): void
	{
		$value = $this->serializer->deserialize([], OptionalPromotedProperties::class);

		$this->assertEquals(
			new OptionalPromotedProperties(
				someString: None::create(),
				someBool: None::create(),
				someInt: None::create(),
			),
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_optional_promoted_properties_invalid_type(): void
	{
		$data = [
			'some_int' => 'im not an int brah!',
			'some_bool' => 'not a bool either',
			'some_string' => [],
		];

		$this->assertDeserializationException(
			fn() => $this->serializer->deserialize($data, OptionalPromotedProperties::class),
			OptionalPromotedProperties::class,
			$data,
			(new FieldErrors())
				->add(new FieldError('some_int', FieldErrorType::ValueIsInvalidType, Some::create('im not an int brah!')))
				->add(new FieldError('some_string', FieldErrorType::ValueIsInvalidType, Some::create([])))
				->add(new FieldError('some_bool', FieldErrorType::ValueIsInvalidType, Some::create('not a bool either'))),
		);
	}

	#[Group('serializer.deserialization')]
	public function test_all_standard_types_with_public_setters(): void
	{
		$data = [
			'some_int' => 1234,
			'some_string' => 'my string',
			'some_bool' => false,
			'some_float' => 7.89,
			'some_array' => [
				'something',
				123,
				false,
			],
			'some_null' => null,
			'some_mixed' => 'blah',
			'some_object' => new stdClass(),
			'some_no_type' => 837,
		];

		$value = $this->serializer->deserialize($data, AllStandardTypesWithPublicSetters::class);

		$expect = new AllStandardTypesWithPublicSetters();
		$expect->someInt = 1234;
		$expect->someString = 'my string';
		$expect->someBool = false;
		$expect->someFloat = 7.89;
		$expect->someArray = [
			'something',
			123,
			false,
		];
		$expect->someNull = null;
		$expect->someMixed = 'blah';
		$expect->someObject = new stdClass();
		$expect->someNoType = 837;

		$this->assertEquals(
			$expect,
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_nullable_fields(): void
	{
		$data = [
			'name' => null,
			'id' => null,
		];

		$value = $this->serializer->deserialize($data, NullableFields::class);

		$expect = new NullableFields(
			name: null,
			id: null,
		);

		$this->assertEquals(
			$expect,
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_nullable_fields_with_values(): void
	{
		$data = [
			'name' => 'my name',
			'id' => 1234,
		];

		$value = $this->serializer->deserialize($data, NullableFields::class);

		$expect = new NullableFields(
			name: 'my name',
			id: 1234,
		);

		$this->assertEquals(
			$expect,
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_nullable_fields_with_invalid_values(): void
	{
		$data = [
			'name' => [],
			'id' => 'not an int',
		];

		$this->assertDeserializationException(
			fn() => $this->serializer->deserialize($data, NullableFields::class),
			NullableFields::class,
			$data,
			(new FieldErrors())
				->add(new FieldError('name', FieldErrorType::ValueIsInvalidType, Some::create([])))
				->add(new FieldError('id', FieldErrorType::ValueIsInvalidType, Some::create('not an int'))),
		);
	}

	#[Group('serializer.deserialization')]
	public function test_default_values(): void
	{
		$data = [];

		$value = $this->serializer->deserialize($data, DefaultValues::class);

		$expect = new DefaultValues();

		$this->assertEquals(
			$expect,
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_object_hierarchy(): void
	{
		$data = [
			'name' => 'top',
			'mid' => [
				'name' => 'mid',
				'btm' => [
					'name' => 'btm',
				],
			],
		];

		$value = $this->serializer->deserialize($data, Top::class);

		$expect = new Top(
			name: 'top',
			mid: new Mid(
				name: 'mid',
				btm: new Btm(
					name: 'btm',
				),
			),
		);

		$this->assertEquals(
			$expect,
			$value,
		);
	}

	#[Group('serializer.deserialization')]
	public function test_union(): void
	{
		$data = [
			'prop' => [
				'name' => 'meh',
			],
		];

		$value = $this->serializer->deserialize($data, Container::class);

		$expect = new Container(
			prop: new TypeA(
				name: 'meh',
			),
		);

		$this->assertEquals(
			$expect,
			$value,
		);
	}
}
