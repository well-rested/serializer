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
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\CollectionNormalizer;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use stdClass;

#[CoversClass(CollectionNormalizer::class)]
#[AllowMockObjectsWithoutExpectations]
class CollectionNormalizerTest extends TestCase
{
	private CollectionNormalizer $normalizer;

	private DenormalizerInterface&MockObject $denormalizer;

	private NormalizerInterface&MockObject $innerNormalizer;

	public function setUp(): void
	{
		parent::setUp();
		$this->normalizer = new CollectionNormalizer();
		$this->denormalizer = $this->createMock(DenormalizerInterface::class);
		$this->innerNormalizer = $this->createMock(NormalizerInterface::class);
		$this->normalizer->withDenormalizer($this->denormalizer);
		$this->normalizer->withNormalizer($this->innerNormalizer);
	}

	/** int-keyed, string-valued collection */
	private function intStringCollection(): CollectionType
	{
		return new CollectionType(
			new GenericType(
				new BuiltinType(TypeIdentifier::ARRAY),
				new BuiltinType(TypeIdentifier::INT),
				new BuiltinType(TypeIdentifier::STRING),
			),
		);
	}

	/** int-keyed, int-valued collection */
	private function intIntCollection(): CollectionType
	{
		return new CollectionType(
			new GenericType(
				new BuiltinType(TypeIdentifier::ARRAY),
				new BuiltinType(TypeIdentifier::INT),
				new BuiltinType(TypeIdentifier::INT),
			),
		);
	}

	// --- supportsDenormalization ---

	public function test_supports_denormalization_for_collection_type(): void
	{
		$this->assertTrue($this->normalizer->supportsDenormalization(
			Some::create([]),
			$this->intIntCollection(),
		));
	}

	public function test_supports_denormalization_for_builtin_array_type(): void
	{
		$this->assertTrue($this->normalizer->supportsDenormalization(
			Some::create([]),
			new BuiltinType(TypeIdentifier::ARRAY),
		));
	}

	public function test_does_not_support_denormalization_for_non_array_builtin(): void
	{
		$this->assertFalse($this->normalizer->supportsDenormalization(
			Some::create(42),
			new BuiltinType(TypeIdentifier::INT),
		));
	}

	// --- denormalize ---

	public function test_denormalize_returns_required_error_when_not_defined(): void
	{
		$result = $this->normalizer->denormalize(None::create(), $this->intIntCollection(), 'items');

		$this->assertInstanceOf(FieldError::class, $result);
		$this->assertSame(FieldErrorType::ValueIsRequired, $result->type);
	}

	public function test_denormalize_returns_empty_array_for_empty_input(): void
	{
		$this->denormalizer->expects($this->never())->method('denormalize');

		$result = $this->normalizer->denormalize(Some::create([]), $this->intIntCollection(), 'items');

		$this->assertSame([], $result);
	}

	public function test_denormalize_returns_processed_items(): void
	{
		$this->denormalizer->method('denormalize')->willReturn(42);

		$result = $this->normalizer->denormalize(
			Some::create([0 => 42]),
			$this->intIntCollection(),
			'items',
		);

		$this->assertSame([0 => 42], $result);
	}

	public function test_denormalize_returns_errors_for_invalid_value_type(): void
	{
		// array value is incompatible with STRING value type
		$result = $this->normalizer->denormalize(
			Some::create([0 => []]),
			$this->intStringCollection(),
			'items',
		);

		$this->assertInstanceOf(FieldErrors::class, $result);
		$errors = iterator_to_array($result);
		$this->assertCount(1, $errors);
		$this->assertSame(FieldErrorType::ValueIsInvalidType, array_values($errors)[0]->type);
	}

	public function test_denormalize_returns_errors_for_invalid_key_type(): void
	{
		// non-numeric string key is incompatible with INT key type
		$result = $this->normalizer->denormalize(
			Some::create(['bad_key' => 'hello']),
			$this->intStringCollection(),
			'items',
		);

		$this->assertInstanceOf(FieldErrors::class, $result);
		$errors = iterator_to_array($result);
		$this->assertCount(1, $errors);
		$this->assertSame(FieldErrorType::InvalidCollectionKeyType, array_values($errors)[0]->type);
	}

	// --- normalize ---

	public function test_normalize_returns_none_when_not_defined(): void
	{
		$this->innerNormalizer->expects($this->never())->method('normalize');

		$result = $this->normalizer->normalize(None::create(), $this->intIntCollection(), 'items');

		$this->assertEquals(None::create(), $result);
	}

	public function test_normalize_maps_items_through_inner_normalizer(): void
	{
		$this->innerNormalizer->method('normalize')->willReturn(42);

		$result = $this->normalizer->normalize(
			Some::create([0 => 42]),
			$this->intIntCollection(),
			'items',
		);

		$this->assertSame([0 => 42], $result);
	}

	// --- supportsNormalization ---

	public function test_supports_normalization_for_collection_type(): void
	{
		$this->assertTrue($this->normalizer->supportsNormalization(
			Some::create([]),
			$this->intIntCollection(),
		));
	}

	public function test_supports_normalization_for_builtin_array_type(): void
	{
		$this->assertTrue($this->normalizer->supportsNormalization(
			Some::create([]),
			new BuiltinType(TypeIdentifier::ARRAY),
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
