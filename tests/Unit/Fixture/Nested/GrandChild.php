<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Nested;

class GrandChild
{
	public function __construct(
		protected string $name,
	) {}
}
