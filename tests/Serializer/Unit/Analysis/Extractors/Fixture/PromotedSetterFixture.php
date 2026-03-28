<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors\Fixture;

class PromotedSetterFixture
{
	public function __construct(
		public int $firstProp,
		public int $secondProp = 0,
	) {}
}
