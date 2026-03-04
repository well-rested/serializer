<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\TypeDefinitions\Fixture;

use WellRested\Serializer\Attributes\Field;
use PhpOption\Option;

class TypesContainer
{
	public $noType;

	#[Field(type: 'int[]|string[]')]
	public $noTypeUseFieldAttribute;

	public int $myInt;

	public ?int $nullableInt;

	#[Field(type: '(int|string)[]')]
	public array $typedArray;

	public int|string $unionStringOrInt;

	public InterfaceA $someInterface;

	public TraitA $someTrait;

	public int|string|(SubTypeA&SubTypeB) $intersectionIntStringOrDateTime;

	public SubTypeA&SubTypeB $simpleIntersection;

	#[Field(type: 'int[]')]
	public Option $optionalIntArray;

	#[Field(type: '(int|string)[]|(' . SubTypeA::class . '&' . SubTypeB::class . '|string)[]|bool|null')]
	public Option $complicatedOption;
}
