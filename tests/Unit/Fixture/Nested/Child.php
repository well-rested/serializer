<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Nested;

class Child
{
	public function __construct(
		protected string $name,
		protected GrandChild $myChild,
	) {}
}
