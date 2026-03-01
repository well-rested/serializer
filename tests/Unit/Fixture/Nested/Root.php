<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Nested;

class Root
{
	public function __construct(
		protected string $id,
		protected int $myNumber,
		public string $reference,
		public Child $child,
		public GrandChild $favouriteGrandChild,
	) {}
}
