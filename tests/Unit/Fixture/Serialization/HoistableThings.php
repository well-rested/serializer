<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Serialization;

use WellRested\Serializer\Attributes\Field;

class HoistableThings
{
	public function __construct(
		#[Field(type: HoistableThing::class . '[]')]
		public array $items,
	) {}
}
