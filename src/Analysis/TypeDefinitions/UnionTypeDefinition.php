<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

use InvalidArgumentException;

class UnionTypeDefinition extends TypeDefinitionAbstract
{
	/**
	 * @var array<int, TypeDefinitionAbstract>
	 */
	protected array $possibleTypes;

	public function __construct(TypeDefinitionAbstract ...$possibleTypes)
	{
		if (count($possibleTypes) < 2) {
			// Otherwise it's not a union...
			throw new InvalidArgumentException('a union type must have at least two possible types');
		}

		$this->possibleTypes = array_unique($possibleTypes);
	}

	/** @return TypeDefinitionAbstract[] */
	public function getPossibleTypes(): array
	{
		return $this->possibleTypes;
	}

	public function getName(): string
	{
		return implode(
			'|',
			array_map(fn(TypeDefinitionAbstract $type) => $type->getName(), $this->possibleTypes),
		);
	}

	public function allowsNull(): bool
	{
		foreach ($this->possibleTypes as $possibleType) {
			if ($possibleType instanceof NullTypeDefinition) {
				return true;
			}
		}

		return false;
	}
}
