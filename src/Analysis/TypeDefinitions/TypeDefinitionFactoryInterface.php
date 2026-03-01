<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

use ReflectionProperty;

interface TypeDefinitionFactoryInterface
{
	public function fromString(string $type): TypeDefinitionAbstract;

	public function fromReflectionProperty(ReflectionProperty $property): TypeDefinitionAbstract;
}
