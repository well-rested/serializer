<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors\Fixture\Polymorphism;

use WellRested\Serializer\Attributes\Polymorphic;
use stdClass;

class PolymorphismFixture
{
	#[Polymorphic(typeMap: ['a' => ConcretePolymorphicA::class, 'b' => ConcretePolymorphicB::class])]
	public mixed $validPolymorphic;

	#[Polymorphic(typeMap: ['a' => ConcretePolymorphicA::class, 'b' => ConcretePolymorphicB::class], field: 'kind')]
	public mixed $customField;

	public mixed $unionWithoutAttribute;

	public mixed $nonUnionProperty;

	// typeMap has 1 entry; tests mock the union to return 2 types → count mismatch
	#[Polymorphic(typeMap: ['a' => ConcretePolymorphicA::class])]
	public mixed $countMismatch;

	// typeMap has 2 entries; first entry is abstract → throws on abstract check
	#[Polymorphic(typeMap: ['a' => AbstractPolymorphic::class, 'b' => ConcretePolymorphicB::class])]
	public mixed $abstractInTypeMap;

	// typeMap has 2 entries; first entry is an interface → throws on interface check
	#[Polymorphic(typeMap: ['a' => PolymorphicInterface::class, 'b' => ConcretePolymorphicB::class])]
	public mixed $interfaceInTypeMap;

	// typeMap has 2 entries; second entry (stdClass) is not in the union → throws on union check
	#[Polymorphic(typeMap: ['a' => ConcretePolymorphicA::class, 'x' => stdClass::class])]
	public mixed $classNotInUnion;
}
