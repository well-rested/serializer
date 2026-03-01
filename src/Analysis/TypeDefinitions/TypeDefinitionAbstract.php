<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

abstract class TypeDefinitionAbstract
{
	abstract public function getName(): string;

	public function __toString(): string
	{
		return $this->getName();
	}

	public function allowsNull(): bool
	{
		return false;
	}

	public function is(string $classString): bool
	{
		return $this instanceof $classString;
	}
}
