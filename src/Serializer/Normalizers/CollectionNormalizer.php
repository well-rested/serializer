<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\Traits\DenormalizesRecursively;
use WellRested\Serializer\Normalizers\Traits\NormalizesRecursively;
use WellRested\Serializer\Normalizers\Traits\ValidatesValueTypes;

class CollectionNormalizer implements DenormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface, NormalizerInterface
{
	use ValidatesValueTypes;
	use DenormalizesRecursively;
	use NormalizesRecursively;

	public function supportsDenormalization(Option $data, Type $type): bool
	{
		return $type instanceof CollectionType
			|| ($type instanceof BuiltinType && $type->getTypeIdentifier() === TypeIdentifier::ARRAY);
	}

	public function denormalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof CollectionType);

		if (! $data->isDefined()) {
			return new FieldError(
				location: $path,
				type: FieldErrorType::ValueIsRequired,
				value: None::create(),
			);
		}

		if ($data->get() === []) {
			return [];
		}

		$fieldErrors = new FieldErrors();

		$denormalizedValue = [];

		$items = $data->get();
		assert(is_array($items));

		foreach ($items as $key => $value) {
			$propertyErrors = $this->validateElement($key, $value, $type, $path);

			if (! $propertyErrors->isEmpty()) {
				$fieldErrors->merge($propertyErrors);
				continue;
			}

			$denormalizedValue[$key] = $this->recursivelyDenormalize(Some::create($value), $type->getCollectionValueType(), $path . '.' . $key);
		}

		if (! $fieldErrors->isEmpty()) {
			return $fieldErrors;
		}

		return $denormalizedValue;
	}

	/**
	 *
	 * @param CollectionType<BuiltinType<TypeIdentifier::ARRAY>|BuiltinType<TypeIdentifier::ITERABLE>|ObjectType<class-string>|GenericType<BuiltinType<TypeIdentifier::ARRAY>|BuiltinType<TypeIdentifier::ITERABLE>|ObjectType<class-string>>> $type
	 */
	protected function validateElement(int|string $key, mixed $value, CollectionType $type, string $path): FieldErrors
	{
		$fieldErrors = new FieldErrors();
		$fullPath = $path . '.' . ((string) $key);

		if (! $this->valueIsCompatibleWithType($key, $type->getCollectionKeyType())) {
			$fieldErrors->add(new FieldError(
				location: $fullPath,
				type: FieldErrorType::InvalidCollectionKeyType,
				// mixed is less strict than int|string, it will accept this
				// @phpstan-ignore-next-line argument.type
				value: Some::create($key),
			));
		}

		if (! $this->valueIsCompatibleWithType($value, $type->getCollectionValueType())) {
			$fieldErrors->add(new FieldError(
				location: $fullPath,
				type: FieldErrorType::ValueIsInvalidType,
				value: Some::create($value),
			));
		}

		return $fieldErrors;
	}

	public function normalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof CollectionType);
		if (! $data->isDefined()) {
			return None::create();
		}

		/** @var array<mixed> */
		$values = $data->get();

		$normalized = [];
		foreach ($values as $key => $value) {
			$normalized[$key] = $this->recursivelyNormalize(
				Some::create($value),
				$type->getCollectionValueType(),
				$path . '.' . (string) $key,
			);
		}

		return $normalized;
	}

	public function supportsNormalization(Option $data, Type $type): bool
	{
		return $type instanceof CollectionType
			|| ($type instanceof BuiltinType && $type->getTypeIdentifier() === TypeIdentifier::ARRAY);
	}
}
