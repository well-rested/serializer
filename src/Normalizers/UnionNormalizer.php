<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers;

use PhpOption\None;
use PhpOption\Option;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\UnionType;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\Traits\DenormalizesRecursively;
use WellRested\Serializer\Normalizers\Traits\NormalizesRecursively;
use WellRested\Serializer\Normalizers\Traits\ValidatesValueTypes;

class UnionNormalizer implements DenormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface, NormalizerInterface
{
	use ValidatesValueTypes;
	use DenormalizesRecursively;
	use NormalizesRecursively;

	public function supportsDenormalization(Option $data, Type $type): bool
	{
		// NullableType extends UnionType, but thats not what we wanna deal with in
		// here.
		return $type instanceof UnionType && ! $type instanceof NullableType;
	}

	public function denormalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof UnionType);

		if (! $data->isDefined()) {
			return new FieldError(
				location: $path,
				type: FieldErrorType::ValueIsRequired,
				value: None::create(),
			);
		}

		foreach ($type->getTypes() as $subType) {
			$value = $this->recursivelyDenormalize($data, $subType, $path);

			if ($value instanceof FieldError || $value instanceof FieldErrors) {
				continue;
			}

			return $value;
		}

		return new FieldError(
			location: $path,
			type: FieldErrorType::UnsatisfiableUnionType,
			value: $data,
		);
	}

	public function normalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof UnionType);

		if (! $data->isDefined()) {
			return None::create();
		}

		foreach ($type->getTypes() as $subType) {
			$value = $this->recursivelyNormalize($data, $subType, $path);

			if ($value instanceof FieldError || $value instanceof FieldErrors || $value instanceof Option) {
				// It was not valid for this particular subtype. Can't/shouldn't have an generic with an union option inside
				// it, SomeClass<Option<ClassA>|Option<ClassB>> doesn't really make sense, it should be Option<SomeClass<ClassA|ClassB>>
				// may need to enforce this.
				//
				// Later when adding proper support for polymorphism we can be smarter, and use some kind of discriminator to
				// tell the exact type we're working with I think.
				continue;
			}

			return $value;
		}

		return new FieldError(
			location: $path,
			type: FieldErrorType::UnsatisfiableUnionType,
			value: $data,
		);
	}

	public function supportsNormalization(Option $data, Type $type): bool
	{
		return $type instanceof UnionType && ! $type instanceof NullableType;
	}
}
