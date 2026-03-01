<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

readonly class AllPromotedReadOnlyClass
{
	public function __construct(
		protected string $someString,
		private bool $someBool,
		public int $someInt,
	) {}
}
