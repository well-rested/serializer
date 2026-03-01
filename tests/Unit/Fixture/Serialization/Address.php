<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Serialization;

class Address
{
	public function __construct(
		public string $line1,
		public ?string $line2,
		public string $city,
		public string $postCode,
	) {}
}
