<?php

declare(strict_types=1);

namespace Tests\Integration;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
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
use Tests\Integration\Fixture\WrappedInt;
use Tests\Integration\Fixture\WrappedPublicPromotedProperties;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysisExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\HoistStrategyExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyDefaultValueExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyGetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertySetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\SerializedPropertyNameExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\WrappingStrategyExtractor;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysisExtractor;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Exceptions\DeserializationException;
use WellRested\Serializer\Naming\SnakeCaseNamingStrategy;
use WellRested\Serializer\Normalizers\CollectionNormalizer;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\FallbackNormalizer;
use WellRested\Serializer\Normalizers\ObjectNormalizer;
use WellRested\Serializer\Normalizers\OptionNormalizer;
use WellRested\Serializer\Normalizers\UnionNormalizer;
use WellRested\Serializer\Serializer;

class SerializationTest extends TestCase
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
					new WrappingStrategyExtractor(),
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
				new FallbackNormalizer(),
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

	#[Group('serializer.serialization')]
	public function test_public_promoted_properties(): void
	{
		$value = $this->serializer->serialize(
			subject: new PublicPromotedProperties(
				someString: 'blah',
				someBool: true,
				someInt: 1234,
			),
		);

		$this->assertEquals(
			[
				'some_string' => 'blah',
				'some_int' => 1234,
				'some_bool' => true,
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_optional_promoted_properties(): void
	{
		$value = $this->serializer->serialize(new OptionalPromotedProperties(
			someString: Some::create('blah'),
			someBool: Some::create(true),
			someInt: Some::create(1234),
		));

		$this->assertEquals(
			[
				'some_string' => 'blah',
				'some_int' => 1234,
				'some_bool' => true,
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_optional_promoted_properties_missing_all(): void
	{
		$value = $this->serializer->serialize(new OptionalPromotedProperties(
			someString: None::create(),
			someBool: None::create(),
			someInt: None::create(),
		));

		$this->assertEquals(
			new stdClass(),
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_all_standard_types_with_public_getters(): void
	{
		$give = new AllStandardTypesWithPublicSetters();
		$give->someInt = 1234;
		$give->someString = 'my string';
		$give->someBool = false;
		$give->someFloat = 7.89;
		$give->someArray = [
			'something',
			123,
			false,
		];
		$give->someNull = null;
		$give->someMixed = 'blah';
		$give->someObject = new stdClass();
		$give->someNoType = 837;

		$value = $this->serializer->serialize($give);

		$this->assertEquals(
			[
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
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_nullable_fields(): void
	{
		$value = $this->serializer->serialize(new NullableFields(
			name: null,
			id: null,
		));

		$this->assertEquals(
			[
				'name' => null,
				'id' => null,
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_nullable_fields_with_values(): void
	{
		$value = $this->serializer->serialize(new NullableFields(
			name: 'my name',
			id: 1234,
		));

		$this->assertEquals(
			[
				'name' => 'my name',
				'id' => 1234,
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_default_values(): void
	{
		$value = $this->serializer->serialize(new DefaultValues());

		$this->assertEquals(
			[
				'name' => 'josephus',
				'id' => 54,
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_object_hierarchy(): void
	{
		$value = $this->serializer->serialize(new Top(
			name: 'top',
			mid: new Mid(
				name: 'mid',
				btm: new Btm(
					name: 'btm',
				),
			),
		));

		$this->assertEquals(
			[
				'name' => 'top',
				'mid' => [
					'name' => 'mid',
					'btm' => [
						'name' => 'btm',
					],
				],
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_union(): void
	{
		$value = $this->serializer->serialize(new Container(
			prop: new TypeA(
				name: 'meh',
			),
		));

		$this->assertEquals(
			[
				'prop' => [
					'name' => 'meh',
				],
			],
			$value,
		);
	}

	#[Group('serializer.serialization')]
	public function test_get_raised_errors_returns_empty_after_successful_serialization(): void
	{
		$this->serializer->serialize(new PublicPromotedProperties(
			someString: 'hello',
			someBool: false,
			someInt: 1,
		));

		$this->assertThatNoErrorsWereRaised();
	}

	#[Group('serializer.serialization')]
	public function test_serialize_stdclass(): void
	{
		$std = new stdClass();
		$std->name = 'hello';
		$std->age = 42;

		$result = $this->serializer->serialize($std);

		$this->assertEquals(['name' => 'hello', 'age' => 42], $result);
	}

	#[Group('serializer.serialization')]
	public function test_supports_normalization_returns_true(): void
	{
		$this->assertTrue($this->serializer->supportsNormalization(
			None::create(),
			new ObjectType(stdClass::class),
		));
	}

	#[Group('serializer.serialization')]
	public function test_normalize_throws_when_no_normalizer_found(): void
	{
		$serializer = new Serializer([]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('no normalizer found for value type:');

		$serializer->normalize(None::create(), new ObjectType(stdClass::class), '');
	}

	#[Group('serializer.serialization')]
	public function test_normalize_skips_non_normalizer_interface_objects(): void
	{
		$denormalizerOnly = new class implements DenormalizerInterface {
			public function denormalize(Option $data, Type $type, string $path): mixed
			{
				return null;
			}

			public function supportsDenormalization(Option $data, Type $type): bool
			{
				return true;
			}
		};

		$serializer = new Serializer([$denormalizerOnly]);

		$this->expectException(RuntimeException::class);

		$serializer->normalize(None::create(), new ObjectType(stdClass::class), '');
	}

	public function test_wrapped_object(): void
	{
		$value = $this->serializer->serialize(
			subject: new WrappedPublicPromotedProperties(
				body: new PublicPromotedProperties(
					someString: 'blah',
					someBool: true,
					someInt: 1234,
				),
			),
		);

		$this->assertEquals(
			[
				'body' => [
					'data' => [
						'some_string' => 'blah',
						'some_int' => 1234,
						'some_bool' => true,
					],
				],
			],
			$value,
		);
	}

	public function test_wrapped_int(): void
	{
		$value = $this->serializer->serialize(
			subject: new WrappedInt(
				blah: 1234,
			),
		);

		$this->assertEquals(
			[
				'blah' => [
					'data' => 1234,
				],
			],
			$value,
		);
	}
}
