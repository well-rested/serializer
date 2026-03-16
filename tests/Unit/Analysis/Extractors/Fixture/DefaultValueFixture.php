<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Extractors\Fixture;

class DefaultValueFixture
{
	public int $withDefault = 42;

	public int $withoutDefault;

	public function __construct(
		public int $promotedWithoutDefault,
		public int $promotedWithDefault = 100,
	) {}
}
