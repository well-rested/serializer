<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

class AllPromoted
{
	public function __construct(
		protected string $someString,
		private bool $someBool,
		public int $someInt,
	) {}
}
