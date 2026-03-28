<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers\Traits;

use RuntimeException;
use stdClass;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Analysis\Types\OptionType;

trait ValidatesValueTypes
{
	protected function valueIsCompatibleWithType(mixed $value, Type $type): bool
	{
		if ($type instanceof OptionType) {
			return $this->valueIsCompatibleWithType($value, $type->getWrappedType());
		}

		if ($type instanceof NullableType && $value === null) {
			return true;
		}

		if ($type instanceof ObjectType && is_array($value)) {
			return true;
		}

		if ($type instanceof ObjectType && $value instanceof stdClass) {
			return true;
		}

		if ($type instanceof CollectionType) {
			return $this->valueIsCompatibleWithCollectionType($value, $type);
		}

		if ($type instanceof BuiltinType) {
			return $this->valueIsCompatibleWithBuiltinType($value, $type);
		}

		if ($type instanceof UnionType) {
			foreach ($type->getTypes() as $memberType) {
				if ($this->valueIsCompatibleWithType($value, $memberType)) {
					return true;
				}
			}
			return false;
		}

		throw new RuntimeException('cannot check compatible type');
	}

	/**
	 * @param BuiltinType<TypeIdentifier> $type
	 */
	protected function valueIsCompatibleWithBuiltinType(mixed $value, BuiltinType $type): bool
	{
		return match ($type->getTypeIdentifier()) {
			TypeIdentifier::MIXED => true,
			TypeIdentifier::NULL => $value === null,
			TypeIdentifier::ARRAY => is_array($value),
			TypeIdentifier::INT => is_int($value) || is_bool($value) || is_float($value) || (is_string($value) && is_numeric($value)),
			TypeIdentifier::FLOAT => is_float($value) || is_int($value) || is_bool($value) || (is_string($value) && is_numeric($value)),
			TypeIdentifier::STRING => is_string($value) || is_int($value) || is_float($value) || is_bool($value),
			TypeIdentifier::BOOL => is_bool($value) || is_int($value) || is_float($value) || (is_string($value) && in_array(strtolower($value), ['true', 'false', 'yes', 'no', 'y', 'n'], true)),
			TypeIdentifier::OBJECT => is_array($value) || $value instanceof stdClass,
			default => false,
		};
	}

	/**
	 * @param CollectionType<BuiltinType<TypeIdentifier::ARRAY>|BuiltinType<TypeIdentifier::ITERABLE>|ObjectType<class-string>|GenericType<BuiltinType<TypeIdentifier::ARRAY>|BuiltinType<TypeIdentifier::ITERABLE>|ObjectType<class-string>>> $type
	 */
	protected function valueIsCompatibleWithCollectionType(mixed $values, CollectionType $type): bool
	{
		if (! is_array($values)) {
			return false;
		}

		foreach ($values as $value) {
			if ($this->valueIsCompatibleWithType($value, $type->getCollectionValueType()) === false) {
				return false;
			}
		}

		foreach (array_keys($values) as $key) {
			if ($this->valueIsCompatibleWithType($key, $type->getCollectionKeyType()) === false) {
				return false;
			}
		}

		return true;
	}
}
