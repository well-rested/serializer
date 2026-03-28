<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture;

class AllStandardTypesWithPublicSetters
{
	public int $someInt;

	public string $someString;

	public bool $someBool;

	public float $someFloat;

	public array $someArray;

	public null $someNull;

	public mixed $someMixed;

	public object $someObject;

	// note a quirk in reflection, where this is considered to have a defualt value of null
	public $someNoType;
}
