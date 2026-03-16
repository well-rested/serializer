<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Extractors\Fixture;

class PromotedSetterFixture
{
	public function __construct(
		public int $firstProp,
		public int $secondProp = 0,
	) {}
}
