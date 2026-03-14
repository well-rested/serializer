<?php

declare(strict_types=1);

namespace Tests\Integration\Analysis\Fixture\Reflector;

class PropertyTypes
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

	public string|int $stringOrInt;

	public SimpleDummy $simpleDummy;

	public ?string $nullableString;

	public ?SimpleDummy $nullableSimpleDummy;

	public SimpleDummy|int $simpleDummyOrInt;

	public SimpleDummy|int|null $nullableSimpleDummyOrInt;

	/**
	 * @var float
	 */
	public int $intWithDocblock;

	/** @var array<string|int, mixed> */
	public array $typeNarrowedArray1;

	/** @var string|null */
	public int $intDocblockedToNullableString;

	/** @var array<int, SimpleDummy> */
	public array $objectValuedArray;

	/** @var SimpleDummy */
	public mixed $mixedDocblockedToObject;

	/** @var string[] */
	public array $stringListArray;
}
