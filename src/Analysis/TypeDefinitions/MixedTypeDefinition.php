<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

class MixedTypeDefinition extends TypeDefinitionAbstract
{
	public function getName(): string
	{
		return 'mixed';
	}
}
