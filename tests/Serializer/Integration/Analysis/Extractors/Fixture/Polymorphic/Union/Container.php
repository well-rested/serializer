<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\Fixture\Polymorphic\Union;

use WellRested\Serializer\Attributes\Polymorphic;

class Container
{
	#[Polymorphic([
		'a' => TypeA::class,
		'b' => TypeB::class,
		'c' => TypeC::class,
	])]
	public TypeA|TypeB|TypeC $someProperty;
}
