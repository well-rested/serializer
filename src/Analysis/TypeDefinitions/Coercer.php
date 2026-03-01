<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

use InvalidArgumentException;

class Coercer implements CoercerInterface
{
	public function canCoerce(TypeDefinitionAbstract $type, mixed $value): bool
	{
		return match (true) {
			$type->is(UnionTypeDefinition::class) => null !== $this->getCoercableTypeForUnion($type, $value),
			$type->is(IntegerTypeDefinition::class) => is_bool($value) || (is_string($value) && is_numeric($value)) || is_float($value),
			$type->is(StringTypeDefinition::class) => is_bool($value) || is_int($value) || is_float($value),
			$type->is(FloatTypeDefinition::class) => is_int($value) || (is_string($value) && is_numeric($value)),
			$type->is(BoolTypeDefinition::class) => is_int($value) || (is_string($value) && $this->isBooleanString($value)) || is_float($value),
			default => false,
		};
	}

	public function coerce(TypeDefinitionAbstract $type, mixed $value): mixed
	{
		return match (true) {
			$type->is(UnionTypeDefinition::class) => $this->coerce($this->getCoercableTypeForUnion($type, $value), $value),
			$type->is(BoolTypeDefinition::class) => $this->castToBool($value),
			$type->is(IntegerTypeDefinition::class) => $this->castToInt($value),
			$type->is(FloatTypeDefinition::class) => $this->castToFloat($value),
			$type->is(StringTypeDefinition::class) => $this->castToString($value),
			default => $value,
		};
	}

	protected function getCoercableTypeForUnion(UnionTypeDefinition $type, mixed $value): ?TypeDefinitionAbstract
	{
		foreach ($type->getPossibleTypes() as $type) {
			if ($this->canCoerce($type, $value)) {
				return $type;
			}
		}

		return null;
	}

	protected function castToBool(mixed $value): bool
	{
		if (is_int($value)) {
			return 0 !== $value;
		}

		if (is_float($value)) {
			return 0 !== $this->castToint($value);
		}

		if (is_string($value) && $this->isBooleanString($value)) {
			$value = strtolower($value);

			return in_array($value, $this->getPositiveBooleanStrings()) ? true : false;
		}

		throw new InvalidArgumentException('cannot cast value to bool');
	}

	protected function castToInt(mixed $value): int
	{
		if (is_float($value)) {
			return (int) round($value);
		}

		if (is_string($value)) {
			return (int) $value;
		}

		if (is_bool($value)) {
			return true === $value ? 1 : 0;
		}

		throw new InvalidArgumentException('cannot cast value to int');
	}

	protected function castToFloat(mixed $value): float
	{
		if (is_int($value)) {
			return (float) $value;
		}

		if (is_string($value)) {
			return (float) $value;
		}

		if (is_bool($value)) {
			return (float) (true === $value ? 1 : 0);
		}

		throw new InvalidArgumentException('cannot cast value to float');
	}

	protected function castToString(mixed $value): string
	{
		if (is_int($value) || is_float($value)) {
			return (string) $value;
		}

		if (is_bool($value)) {
			return true === $value ? 'true' : 'false';
		}

		throw new InvalidArgumentException('cannot cast value to string');
	}

	protected function isBooleanString($value): bool
	{
		$value = strtolower($value);

		return in_array($value, $this->getNegativeBooleanStrings()) || in_array($value, $this->getPositiveBooleanStrings());
	}

	protected function getPositiveBooleanStrings(): array
	{
		return [
			'yes',
			'y',
			'true',
		];
	}

	protected function getNegativeBooleanStrings(): array
	{
		return [
			'no',
			'n',
			'false',
		];
	}
}
