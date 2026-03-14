<?php

declare(strict_types=1);

namespace Tests\Integration\Fixture\Hierarchy;

class Top
{
	public function __construct(
		public string $name,
		public Mid $mid,
	) {}
}
