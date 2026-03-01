<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

class ClassTypeDefinition extends TypeDefinitionAbstract
{
	public function __construct(
		protected string $fqdn,
	) {}

	public function getName(): string
	{
		return $this->fqdn;
	}
}
