<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Extractors\Fixture\Hoist;

use WellRested\Serializer\Attributes\Hoist;

class HoistFixture
{
	#[Hoist('someProp')]
	public mixed $propertyWithHoist;

	public mixed $propertyWithoutHoist;
}
