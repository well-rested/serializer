<?php

declare(strict_types=1);

namespace Tests\Unit\Normalizers;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\FallbackNormalizer;
use stdClass;

#[CoversClass(FallbackNormalizer::class)]
class GenericNormalizerTest extends TestCase
{
	private FallbackNormalizer $normalizer;

	public function setUp(): void
	{
		parent::setUp();
		$this->normalizer = new FallbackNormalizer();
	}

	// --- supportsDenormalization ---

	public function test_supports_denormalization_for_builtin_type(): void
	{
		$this->assertTrue($this->normalizer->supportsDenormalization(
			Some::create(42),
			new BuiltinType(TypeIdentifier::INT),
		));
	}

	public function test_supports_denormalization_for_nullable_type(): void
	{
		$this->assertTrue($this->normalizer->supportsDenormalization(
			None::create(),
			new NullableType(new BuiltinType(TypeIdentifier::INT)),
		));
	}

	public function test_does_not_support_denormalization_for_other_type(): void
	{
		$this->assertFalse($this->normalizer->supportsDenormalization(
			Some::create([]),
			new ObjectType(stdClass::class),
		));
	}

	// --- denormalize ---

	public function test_denormalize_returns_required_error_when_not_defined(): void
	{
		$result = $this->normalizer->denormalize(
			None::create(),
			new BuiltinType(TypeIdentifier::INT),
			'field',
		);

		$this->assertInstanceOf(FieldError::class, $result);
		$this->assertSame(FieldErrorType::ValueIsRequired, $result->type);
	}

	public function test_denormalize_returns_value_when_compatible(): void
	{
		$result = $this->normalizer->denormalize(
			Some::create(42),
			new BuiltinType(TypeIdentifier::INT),
			'field',
		);

		$this->assertSame(42, $result);
	}

	public function test_denormalize_returns_invalid_type_error_when_incompatible(): void
	{
		$result = $this->normalizer->denormalize(
			Some::create([]),
			new BuiltinType(TypeIdentifier::INT),
			'field',
		);

		$this->assertInstanceOf(FieldError::class, $result);
		$this->assertSame(FieldErrorType::ValueIsInvalidType, $result->type);
	}

	// --- normalize ---

	public function test_normalize_returns_none_when_not_defined(): void
	{
		$result = $this->normalizer->normalize(
			None::create(),
			new BuiltinType(TypeIdentifier::INT),
			'field',
		);

		$this->assertEquals(None::create(), $result);
	}

	public function test_normalize_returns_raw_value(): void
	{
		$result = $this->normalizer->normalize(
			Some::create(42),
			new BuiltinType(TypeIdentifier::INT),
			'field',
		);

		$this->assertSame(42, $result);
	}

	// --- supportsNormalization ---

	public function test_supports_normalization_for_builtin_type(): void
	{
		$this->assertTrue($this->normalizer->supportsNormalization(
			Some::create(42),
			new BuiltinType(TypeIdentifier::INT),
		));
	}

	public function test_supports_normalization_for_nullable_type(): void
	{
		$this->assertTrue($this->normalizer->supportsNormalization(
			None::create(),
			new NullableType(new BuiltinType(TypeIdentifier::INT)),
		));
	}

	public function test_does_not_support_normalization_for_other_type(): void
	{
		$this->assertFalse($this->normalizer->supportsNormalization(
			Some::create([]),
			new ObjectType(stdClass::class),
		));
	}
}
