<?php

declare(strict_types=1);

namespace Tests\Integration\Fixture\Hierarchy;

class Mid
{
	public function __construct(
		public string $name,
		public Btm $btm,
	) {}
}
