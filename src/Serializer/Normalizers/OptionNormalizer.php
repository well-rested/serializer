<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use Symfony\Component\TypeInfo\Type;
use WellRested\Serializer\Analysis\Types\OptionType;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\Traits\DenormalizesRecursively;
use WellRested\Serializer\Normalizers\Traits\NormalizesRecursively;

class OptionNormalizer implements DenormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface, NormalizerInterface
{
	use DenormalizesRecursively;
	use NormalizesRecursively;

	public function supportsDenormalization(Option $data, Type $type): bool
	{
		return $type instanceof OptionType;
	}

	public function denormalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof OptionType);

		if (! $data->isDefined()) {
			return None::create();
		}

		$value = $this->recursivelyDenormalize($data, $type->getWrappedType(), $path);

		if ($value instanceof FieldError || $value instanceof FieldErrors) {
			return $value;
		}

		return Some::create($value);
	}

	public function normalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof OptionType);
		if (! $data->isDefined()) {
			return None::create();
		}

		$value = $data->get();

		if (! $value instanceof Option) {
			return new FieldError(
				location: $path,
				type: FieldErrorType::ValueIsInvalidType,
				value: Some::create($value),
			);
		}

		return $value->isDefined()
			? $this->recursivelyNormalize($value, $type->getWrappedType(), $path)
			: None::create();
	}

	public function supportsNormalization(Option $data, Type $type): bool
	{
		return $type instanceof OptionType;
	}
}
