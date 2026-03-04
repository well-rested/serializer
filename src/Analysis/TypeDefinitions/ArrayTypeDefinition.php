<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

class ArrayTypeDefinition extends TypeDefinitionAbstract
{
	public function __construct(
		protected TypeDefinitionAbstract $itemType,
	) {}

	public function getItemType(): TypeDefinitionAbstract
	{
		return $this->itemType;
	}

	public function getName(): string
	{
		return $this->itemType->getName() . '[]';
	}
}
