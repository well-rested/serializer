<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

interface CoercerInterface
{
	public function canCoerce(TypeDefinitionAbstract $type, mixed $value): bool;

	public function coerce(TypeDefinitionAbstract $type, mixed $value): mixed;
}
