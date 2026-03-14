<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers\Contracts;

use PhpOption\Option;
use Symfony\Component\TypeInfo\Type;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;

interface NormalizerInterface
{
	/**
	 * @param Option<mixed> $data
	 */
	public function supportsNormalization(Option $data, Type $type): bool;

	/**
	 * Normalize $data into the value described by $type.
	 *
	 * Return values:
	 *   - The normalized value on success
	 *   - FieldError  — a single field-level problem (wrong type, etc.)
	 *   - FieldErrors — multiple problems collected from nested fields (e.g. from
	 *                   object deserialization); the serializer will merge these
	 *                   into its error collection
	 *
	 * @param Option<mixed> $data
	 *
	 * @return mixed
	 */
	public function normalize(Option $data, Type $type, string $path): mixed;
}
