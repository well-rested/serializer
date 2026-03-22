<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers;

use PhpOption\None;
use PhpOption\Option;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\NullableType;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\Traits\ValidatesValueTypes;

class FallbackNormalizer implements DenormalizerInterface, NormalizerInterface
{
	use ValidatesValueTypes;

	public function supportsDenormalization(Option $data, Type $type): bool
	{
		return $type instanceof BuiltinType || $type instanceof NullableType;
	}

	public function denormalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof BuiltinType || $type instanceof NullableType);

		if (! $data->isDefined()) {
			return new FieldError(
				location: $path,
				type: FieldErrorType::ValueIsRequired,
				value: None::create(),
			);
		}

		$value = $data->get();

		if (! $this->valueIsCompatibleWithType($value, $type)) {
			return new FieldError(
				location: $path,
				type: FieldErrorType::ValueIsInvalidType,
				value: $data,
			);
		}

		return $value;
	}

	public function normalize(Option $data, Type $type, string $path): mixed
	{
		if (! $data->isDefined()) {
			return None::create();
		}

		return $data->get();
	}

	public function supportsNormalization(Option $data, Type $type): bool
	{
		return $type instanceof BuiltinType || $type instanceof NullableType;
	}
}
