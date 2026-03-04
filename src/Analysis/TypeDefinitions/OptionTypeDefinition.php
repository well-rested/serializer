<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

class OptionTypeDefinition extends TypeDefinitionAbstract
{
	public function __construct(
		protected TypeDefinitionAbstract $type,
	) {}

	public function getWrappedType(): TypeDefinitionAbstract
	{
		return $this->type;
	}

	public function getName(): string
	{
		return 'Option<' . $this->type->getName() . '>';
	}
}
