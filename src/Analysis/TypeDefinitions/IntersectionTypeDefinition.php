<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

use InvalidArgumentException;

class IntersectionTypeDefinition extends TypeDefinitionAbstract
{
	/**
	 * @var array<int, TypeDefinitionAbstract>
	 */
	protected array $possibleTypes;

	public function __construct(TypeDefinitionAbstract ...$possibleTypes)
	{
		// TODO: maybe add some more validation around the actual types we allow here
		// Can't really have an intersection with an array type or a scalar for example.
		if (count($possibleTypes) < 2) {
			// Otherwise it's not a union...
			throw new InvalidArgumentException('a union type must have at least two possible types');
		}

		$this->possibleTypes = array_unique(array_values($possibleTypes));
	}

	public function getName(): string
	{
		return implode(
			'&',
			array_map(fn(TypeDefinitionAbstract $type) => $type->getName(), $this->possibleTypes),
		);
	}
}
