<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

class NullTypeDefinition extends TypeDefinitionAbstract
{
	public function getName(): string
	{
		return 'null';
	}

	public function allowsNull(): bool
	{
		return true;
	}
}
