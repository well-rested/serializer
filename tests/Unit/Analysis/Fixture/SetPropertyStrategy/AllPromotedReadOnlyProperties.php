<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

class AllPromotedReadOnlyProperties
{
	public function __construct(
		protected readonly string $someString,
		private readonly bool $someBool,
		public readonly int $someInt,
	) {}
}
