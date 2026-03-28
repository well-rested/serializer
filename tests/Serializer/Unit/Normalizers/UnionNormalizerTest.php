<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Normalizers;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\UnionNormalizer;
use stdClass;

#[CoversClass(UnionNormalizer::class)]
#[AllowMockObjectsWithoutExpectations]
class UnionNormalizerTest extends TestCase
{
	private UnionNormalizer $normalizer;

	private DenormalizerInterface&MockObject $denormalizer;

	private NormalizerInterface&MockObject $innerNormalizer;

	public function setUp(): void
	{
		parent::setUp();
		$this->normalizer = new UnionNormalizer();
		$this->denormalizer = $this->createMock(DenormalizerInterface::class);
		$this->innerNormalizer = $this->createMock(NormalizerInterface::class);
		$this->normalizer->withDenormalizer($this->denormalizer);
		$this->normalizer->withNormalizer($this->innerNormalizer);
	}

	private function unionType(): UnionType
	{
		return new UnionType(
			new BuiltinType(TypeIdentifier::INT),
			new BuiltinType(TypeIdentifier::ARRAY),
		);
	}

	// --- supportsDenormalization ---

	public function test_supports_denormalization_for_union_type(): void
	{
		$this->assertTrue($this->normalizer->supportsDenormalization(None::create(), $this->unionType()));
	}

	public function test_does_not_support_denormalization_for_nullable_type(): void
	{
		$this->assertFalse($this->normalizer->supportsDenormalization(
			None::create(),
			new NullableType(new BuiltinType(TypeIdentifier::INT)),
		));
	}

	public function test_does_not_support_denormalization_for_other_type(): void
	{
		$this->assertFalse($this->normalizer->supportsDenormalization(
			None::create(),
			new ObjectType(stdClass::class),
		));
	}

	// --- denormalize ---

	public function test_denormalize_returns_required_error_when_not_defined(): void
	{
		$this->denormalizer->expects($this->never())->method('denormalize');

		$result = $this->normalizer->denormalize(None::create(), $this->unionType(), 'field');

		$this->assertInstanceOf(FieldError::class, $result);
		$this->assertSame(FieldErrorType::ValueIsRequired, $result->type);
	}

	public function test_denormalize_returns_first_successful_result(): void
	{
		$this->denormalizer->method('denormalize')->willReturn(42);

		$result = $this->normalizer->denormalize(Some::create(42), $this->unionType(), 'field');

		$this->assertSame(42, $result);
	}

	public function test_denormalize_returns_unsatisfiable_error_when_no_subtype_matches(): void
	{
		$error = new FieldError(location: 'field', type: FieldErrorType::ValueIsInvalidType, value: Some::create('x'));

		$this->denormalizer->method('denormalize')->willReturn($error);

		$result = $this->normalizer->denormalize(Some::create('x'), $this->unionType(), 'field');

		$this->assertInstanceOf(FieldError::class, $result);
		$this->assertSame(FieldErrorType::UnsatisfiableUnionType, $result->type);
	}

	// --- normalize ---

	public function test_normalize_returns_none_when_not_defined(): void
	{
		$this->innerNormalizer->expects($this->never())->method('normalize');

		$result = $this->normalizer->normalize(None::create(), $this->unionType(), 'field');

		$this->assertEquals(None::create(), $result);
	}

	public function test_normalize_returns_first_successful_result(): void
	{
		$this->innerNormalizer->method('normalize')->willReturn(42);

		$result = $this->normalizer->normalize(Some::create(42), $this->unionType(), 'field');

		$this->assertSame(42, $result);
	}

	public function test_normalize_returns_unsatisfiable_error_when_all_subtypes_fail(): void
	{
		$error = new FieldError(location: 'field', type: FieldErrorType::ValueIsInvalidType, value: Some::create('x'));

		$this->innerNormalizer->method('normalize')->willReturn($error);

		$result = $this->normalizer->normalize(Some::create('x'), $this->unionType(), 'field');

		$this->assertInstanceOf(FieldError::class, $result);
		$this->assertSame(FieldErrorType::UnsatisfiableUnionType, $result->type);
	}

	// --- supportsNormalization ---

	public function test_supports_normalization_for_union_type(): void
	{
		$this->assertTrue($this->normalizer->supportsNormalization(None::create(), $this->unionType()));
	}

	public function test_does_not_support_normalization_for_nullable_type(): void
	{
		$this->assertFalse($this->normalizer->supportsNormalization(
			None::create(),
			new NullableType(new BuiltinType(TypeIdentifier::INT)),
		));
	}
}
