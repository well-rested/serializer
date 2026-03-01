<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Serialization;

class HoistableThing
{
	public function __construct(
		public string $id,
		public bool $enabled,
	) {}
}
