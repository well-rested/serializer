<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Normalizers;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tests\Serializer\Unit\Normalizers\Fixture\DtoWithAccessors;
use Tests\Serializer\Unit\Normalizers\Fixture\PromotedDto;
use Tests\Serializer\Unit\Normalizers\Fixture\SimpleDto;
use WellRested\Serializer\Analysis\Extractors\ClassAnalyses;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysis;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysisExtractor;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalyses;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysis;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysisExtractionExtensions;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyGetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertySetterMethodExtractor;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\ObjectNormalizer;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(ObjectNormalizer::class)]
#[AllowMockObjectsWithoutExpectations]
class ObjectNormalizerTest extends TestCase
{
	private ObjectNormalizer $normalizer;

	private ClassAnalysisExtractor&MockObject $extractor;

	private DenormalizerInterface&MockObject $denormalizer;

	private NormalizerInterface&MockObject $innerNormalizer;

	public function setUp(): void
	{
		parent::setUp();
		$this->extractor = $this->createMock(ClassAnalysisExtractor::class);
		$this->normalizer = new ObjectNormalizer($this->extractor);
		$this->denormalizer = $this->createMock(DenormalizerInterface::class);
		$this->innerNormalizer = $this->createMock(NormalizerInterface::class);
		$this->normalizer->withDenormalizer($this->denormalizer);
		$this->normalizer->withNormalizer($this->innerNormalizer);
	}

	private function publicSetterPropertyAnalysis(string $name): PropertyAnalysis
	{
		return new PropertyAnalysis(
			name: $name,
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					PropertySetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					)),
				)
				->add(
					PropertyGetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					)),
				),
		);
	}

	private function constructorArgPropertyAnalysis(string $name, int $index): PropertyAnalysis
	{
		return new PropertyAnalysis(
			name: $name,
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					PropertySetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::ConstructorArgument,
						constructorIndex: $index,
					)),
				),
		);
	}

	private function setterMethodPropertyAnalysis(string $name, string $setter, string $getter): PropertyAnalysis
	{
		return new PropertyAnalysis(
			name: $name,
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					PropertySetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::SetterMethod,
						setterMethod: $setter,
					)),
				)
				->add(
					PropertyGetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::GetterMethod,
						getterMethod: $getter,
					)),
				),
		);
	}

	private function promotedDtoAnalysis(): ClassAnalyses
	{
		$properties = new PropertyAnalyses();
		$properties->add('age', $this->constructorArgPropertyAnalysis('age', 0));

		$analyses = new ClassAnalyses();
		$analyses->add(PromotedDto::class, new ClassAnalysis(
			name: PromotedDto::class,
			properties: $properties,
		));

		return $analyses;
	}

	private function accessorDtoAnalysis(): ClassAnalyses
	{
		$properties = new PropertyAnalyses();
		$properties->add('age', $this->setterMethodPropertyAnalysis('age', 'setAge', 'getAge'));

		$analyses = new ClassAnalyses();
		$analyses->add(DtoWithAccessors::class, new ClassAnalysis(
			name: DtoWithAccessors::class,
			properties: $properties,
		));

		return $analyses;
	}

	private function simpleDtoAnalysis(): ClassAnalyses
	{
		$properties = new PropertyAnalyses();
		$properties->add('age', $this->publicSetterPropertyAnalysis('age'));

		$analyses = new ClassAnalyses();
		$analyses->add(SimpleDto::class, new ClassAnalysis(
			name: SimpleDto::class,
			properties: $properties,
		));

		return $analyses;
	}

	// --- supportsDenormalization ---

	public function test_supports_denormalization_for_object_type_with_array_data(): void
	{
		$this->assertTrue($this->normalizer->supportsDenormalization(
			Some::create(['age' => 25]),
			new ObjectType(SimpleDto::class),
		));
	}

	public function test_does_not_support_denormalization_for_non_object_type(): void
	{
		$this->assertFalse($this->normalizer->supportsDenormalization(
			Some::create([]),
			new BuiltinType(TypeIdentifier::ARRAY),
		));
	}

	// --- denormalize ---

	public function test_denormalize_throws_when_analysis_not_found(): void
	{
		$this->extractor->method('extract')->willReturn(new ClassAnalyses());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('analysis not found for: ' . SimpleDto::class);

		$this->normalizer->denormalize(
			Some::create(['age' => 25]),
			new ObjectType(SimpleDto::class),
			'',
		);
	}

	public function test_denormalize_returns_instance_with_properties_set(): void
	{
		$this->extractor->method('extract')->willReturn($this->simpleDtoAnalysis());
		$this->denormalizer->method('denormalize')->willReturn(25);

		$result = $this->normalizer->denormalize(
			Some::create(['age' => 25]),
			new ObjectType(SimpleDto::class),
			'',
		);

		$this->assertInstanceOf(SimpleDto::class, $result);
		$this->assertSame(25, $result->age);
	}

	public function test_denormalize_returns_field_errors_when_property_resolution_fails(): void
	{
		$this->extractor->method('extract')->willReturn($this->simpleDtoAnalysis());

		$error = new FieldError(location: 'age', type: FieldErrorType::ValueIsRequired, value: None::create());
		$this->denormalizer->method('denormalize')->willReturn($error);

		$result = $this->normalizer->denormalize(
			Some::create([]),
			new ObjectType(SimpleDto::class),
			'',
		);

		$this->assertInstanceOf(FieldErrors::class, $result);
	}

	// --- normalize ---

	public function test_normalize_returns_empty_stdclass_when_not_defined(): void
	{
		$result = $this->normalizer->normalize(
			None::create(),
			new ObjectType(SimpleDto::class),
			'',
		);

		$this->assertEquals(new stdClass(), $result);
	}

	public function test_normalize_throws_when_analysis_not_found(): void
	{
		$this->extractor->method('extract')->willReturn(new ClassAnalyses());

		$dto = new SimpleDto();
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('analysis not found for: ' . SimpleDto::class);

		$this->normalizer->normalize(Some::create($dto), new ObjectType(SimpleDto::class), '');
	}

	public function test_normalize_returns_array_of_normalized_properties(): void
	{
		$this->extractor->method('extract')->willReturn($this->simpleDtoAnalysis());
		$this->innerNormalizer->method('normalize')->willReturn(25);

		$dto = new SimpleDto();
		$dto->age = 25;

		$result = $this->normalizer->normalize(Some::create($dto), new ObjectType(SimpleDto::class), '');

		$this->assertSame(['age' => 25], $result);
	}

	public function test_normalize_returns_stdclass_when_no_properties_produce_values(): void
	{
		$this->extractor->method('extract')->willReturn($this->simpleDtoAnalysis());
		// normalizer returns None for the property, so it's skipped
		$this->innerNormalizer->method('normalize')->willReturn(None::create());

		$dto = new SimpleDto();

		$result = $this->normalizer->normalize(Some::create($dto), new ObjectType(SimpleDto::class), '');

		$this->assertEquals(new stdClass(), $result);
	}

	// --- supportsNormalization ---

	public function test_supports_normalization_for_object_type(): void
	{
		$this->assertTrue($this->normalizer->supportsNormalization(
			Some::create(new SimpleDto()),
			new ObjectType(SimpleDto::class),
		));
	}

	public function test_does_not_support_normalization_for_non_object_type(): void
	{
		$this->assertFalse($this->normalizer->supportsNormalization(
			Some::create([]),
			new BuiltinType(TypeIdentifier::ARRAY),
		));
	}

	public function test_denormalize_returns_field_errors_when_constructor_arg_resolves_to_field_errors(): void
	{
		$this->extractor->method('extract')->willReturn($this->promotedDtoAnalysis());

		$nestedErrors = new FieldErrors();
		$nestedErrors->add(new FieldError(location: 'age.x', type: FieldErrorType::ValueIsRequired, value: None::create()));
		$this->denormalizer->method('denormalize')->willReturn($nestedErrors);

		$result = $this->normalizer->denormalize(
			Some::create(['age' => []]),
			new ObjectType(PromotedDto::class),
			'',
		);

		$this->assertInstanceOf(FieldErrors::class, $result);
	}

	public function test_denormalize_returns_field_errors_when_set_property_resolves_to_field_errors(): void
	{
		$this->extractor->method('extract')->willReturn($this->simpleDtoAnalysis());

		$nestedErrors = new FieldErrors();
		$nestedErrors->add(new FieldError(location: 'age.x', type: FieldErrorType::ValueIsRequired, value: None::create()));
		$this->denormalizer->method('denormalize')->willReturn($nestedErrors);

		$result = $this->normalizer->denormalize(
			Some::create(['age' => []]),
			new ObjectType(SimpleDto::class),
			'',
		);

		$this->assertInstanceOf(FieldErrors::class, $result);
	}

	public function test_denormalize_calls_setter_method_when_strategy_is_setter_method(): void
	{
		$this->extractor->method('extract')->willReturn($this->accessorDtoAnalysis());
		$this->denormalizer->method('denormalize')->willReturn(42);

		$result = $this->normalizer->denormalize(
			Some::create(['age' => 42]),
			new ObjectType(DtoWithAccessors::class),
			'',
		);

		$this->assertInstanceOf(DtoWithAccessors::class, $result);
		$this->assertSame(42, $result->getAge());
	}

	public function test_normalize_uses_getter_method_when_strategy_is_getter_method(): void
	{
		$this->extractor->method('extract')->willReturn($this->accessorDtoAnalysis());
		$this->innerNormalizer->method('normalize')->willReturn(42);

		$dto = new DtoWithAccessors();
		$dto->setAge(42);

		$result = $this->normalizer->normalize(Some::create($dto), new ObjectType(DtoWithAccessors::class), '');

		$this->assertSame(['age' => 42], $result);
	}

	public function test_denormalize_returns_field_errors_when_constructor_arg_resolves_to_single_field_error(): void
	{
		$this->extractor->method('extract')->willReturn($this->promotedDtoAnalysis());

		$error = new FieldError(location: 'age', type: FieldErrorType::ValueIsInvalidType, value: Some::create('bad'));
		$this->denormalizer->method('denormalize')->willReturn($error);

		$result = $this->normalizer->denormalize(
			Some::create(['age' => 'bad']),
			new ObjectType(PromotedDto::class),
			'',
		);

		$this->assertInstanceOf(FieldErrors::class, $result);
	}

	public function test_denormalize_success_with_promoted_property(): void
	{
		$this->extractor->method('extract')->willReturn($this->promotedDtoAnalysis());
		$this->denormalizer->method('denormalize')->willReturn(25);

		$result = $this->normalizer->denormalize(
			Some::create(['age' => 25]),
			new ObjectType(PromotedDto::class),
			'',
		);

		$this->assertInstanceOf(PromotedDto::class, $result);
		$this->assertSame(25, $result->age);
	}

	public function test_normalize_skips_property_with_not_available_getter(): void
	{
		// PropertyAnalysis with no getter extension defaults to NotAvailable
		$properties = new PropertyAnalyses();
		$properties->add('age', new PropertyAnalysis(
			name: 'age',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: new PropertyAnalysisExtractionExtensions(),
		));

		$analyses = new ClassAnalyses();
		$analyses->add(SimpleDto::class, new ClassAnalysis(
			name: SimpleDto::class,
			properties: $properties,
		));

		$this->extractor->method('extract')->willReturn($analyses);
		$this->innerNormalizer->expects($this->never())->method('normalize');

		$result = $this->normalizer->normalize(Some::create(new SimpleDto()), new ObjectType(SimpleDto::class), '');

		$this->assertEquals(new stdClass(), $result);
	}

	public function test_normalize_throws_when_normalizer_returns_defined_some(): void
	{
		$this->extractor->method('extract')->willReturn($this->simpleDtoAnalysis());
		$this->innerNormalizer->method('normalize')->willReturn(Some::create(42));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('should not receieve an option here, unless its none');

		$this->normalizer->normalize(Some::create(new SimpleDto()), new ObjectType(SimpleDto::class), '');
	}
}
