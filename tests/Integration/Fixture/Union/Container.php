<?php

declare(strict_types=1);

namespace Tests\Integration\Fixture\Union;

class Container
{
	public function __construct(
		public TypeA|TypeB $prop,
	) {}
}
