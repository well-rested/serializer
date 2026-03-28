<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers\Contracts;

use PhpOption\Option;
use Symfony\Component\TypeInfo\Type;
use WellRested\Serializer\Errors\FieldError;

interface DenormalizerInterface
{
	/**
	 * @param Option<mixed> $data
	 */
	public function supportsDenormalization(Option $data, Type $type): bool;

	/**
	 * Denormalize $data into the value described by $type.
	 *
	 * Use $recurse to delegate nested values back through the full normalizer
	 * pipeline:
	 *
	 *   $recurse($nestedData, $nestedType, $nestedPath)
	 *
	 * Return values:
	 *   - The denormalized value on success
	 *   - FieldError  — a single field-level problem (wrong type, etc.)
	 *   - FieldErrors — multiple problems collected from nested fields (e.g. from
	 *                   object deserialization); the serializer will merge these
	 *                   into its error collection
	 *
	 * @param Option<mixed> $data
	 *
	 * @return mixed|FieldError|FieldErrors
	 */
	public function denormalize(Option $data, Type $type, string $path): mixed;
}
