<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture\Union;

use WellRested\Serializer\Attributes\Polymorphic;

class Container
{
	public function __construct(
		#[Polymorphic(typeMap: [
			'a' => TypeA::class,
			'b' => TypeB::class,
		])]
		public TypeA|TypeB $prop,
	) {}
}
