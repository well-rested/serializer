<?php

declare(strict_types=1);

namespace Tests\Unit\Normalizers;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Analysis\Types\OptionType;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\OptionNormalizer;
use stdClass;

#[CoversClass(OptionNormalizer::class)]
#[AllowMockObjectsWithoutExpectations]
class OptionNormalizerTest extends TestCase
{
	private OptionNormalizer $normalizer;

	private DenormalizerInterface&MockObject $denormalizer;

	private NormalizerInterface&MockObject $innerNormalizer;

	public function setUp(): void
	{
		parent::setUp();
		$this->normalizer = new OptionNormalizer();
		$this->denormalizer = $this->createMock(DenormalizerInterface::class);
		$this->innerNormalizer = $this->createMock(NormalizerInterface::class);
		$this->normalizer->withDenormalizer($this->denormalizer);
		$this->normalizer->withNormalizer($this->innerNormalizer);
	}

	// --- supportsDenormalization ---

	public function test_supports_denormalization_for_option_type(): void
	{
		$this->assertTrue($this->normalizer->supportsDenormalization(
			None::create(),
			new OptionType(new BuiltinType(TypeIdentifier::INT)),
		));
	}

	public function test_does_not_support_denormalization_for_other_type(): void
	{
		$this->assertFalse($this->normalizer->supportsDenormalization(
			None::create(),
			new BuiltinType(TypeIdentifier::INT),
		));
	}

	// --- denormalize ---

	public function test_denormalize_returns_none_when_not_defined(): void
	{
		$this->denormalizer->expects($this->never())->method('denormalize');

		$result = $this->normalizer->denormalize(
			None::create(),
			new OptionType(new BuiltinType(TypeIdentifier::INT)),
			'field',
		);

		$this->assertEquals(None::create(), $result);
	}

	public function test_denormalize_wraps_result_in_some(): void
	{
		$data = Some::create(42);
		$wrappedType = new BuiltinType(TypeIdentifier::INT);

		$this->denormalizer->expects($this->once())
			->method('denormalize')
			->with($data, $wrappedType, 'field')
			->willReturn(42);

		$result = $this->normalizer->denormalize($data, new OptionType($wrappedType), 'field');

		$this->assertEquals(Some::create(42), $result);
	}

	public function test_denormalize_passes_through_field_error(): void
	{
		$error = new FieldError(location: 'field', type: FieldErrorType::ValueIsRequired, value: None::create());

		$this->denormalizer->method('denormalize')->willReturn($error);

		$result = $this->normalizer->denormalize(
			Some::create(null),
			new OptionType(new BuiltinType(TypeIdentifier::INT)),
			'field',
		);

		$this->assertSame($error, $result);
	}

	public function test_denormalize_passes_through_field_errors(): void
	{
		$errors = new FieldErrors();
		$errors->add(new FieldError(location: 'field', type: FieldErrorType::ValueIsRequired, value: None::create()));

		$this->denormalizer->method('denormalize')->willReturn($errors);

		$result = $this->normalizer->denormalize(
			Some::create([]),
			new OptionType(new ObjectType(stdClass::class)),
			'field',
		);

		$this->assertSame($errors, $result);
	}

	// --- normalize ---

	public function test_normalize_returns_none_when_not_defined(): void
	{
		$this->innerNormalizer->expects($this->never())->method('normalize');

		$result = $this->normalizer->normalize(
			None::create(),
			new OptionType(new BuiltinType(TypeIdentifier::INT)),
			'field',
		);

		$this->assertEquals(None::create(), $result);
	}

	public function test_normalize_delegates_some_value_to_inner_normalizer(): void
	{
		$inner = Some::create(42);
		$wrappedType = new BuiltinType(TypeIdentifier::INT);

		$this->innerNormalizer->expects($this->once())
			->method('normalize')
			->with($inner, $wrappedType, 'field')
			->willReturn(42);

		$result = $this->normalizer->normalize(Some::create($inner), new OptionType($wrappedType), 'field');

		$this->assertSame(42, $result);
	}

	public function test_normalize_returns_none_when_value_is_none(): void
	{
		$this->innerNormalizer->expects($this->never())->method('normalize');

		$result = $this->normalizer->normalize(
			Some::create(None::create()),
			new OptionType(new BuiltinType(TypeIdentifier::INT)),
			'field',
		);

		$this->assertEquals(None::create(), $result);
	}

	public function test_normalize_returns_invalid_type_error_when_value_is_not_option(): void
	{
		$result = $this->normalizer->normalize(
			Some::create(42),
			new OptionType(new BuiltinType(TypeIdentifier::INT)),
			'field',
		);

		$this->assertInstanceOf(FieldError::class, $result);
		$this->assertSame(FieldErrorType::ValueIsInvalidType, $result->type);
	}

	// --- supportsNormalization ---

	public function test_supports_normalization_for_option_type(): void
	{
		$this->assertTrue($this->normalizer->supportsNormalization(
			None::create(),
			new OptionType(new BuiltinType(TypeIdentifier::INT)),
		));
	}

	public function test_does_not_support_normalization_for_other_type(): void
	{
		$this->assertFalse($this->normalizer->supportsNormalization(
			None::create(),
			new BuiltinType(TypeIdentifier::INT),
		));
	}
}
