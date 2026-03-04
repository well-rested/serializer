<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Serialization;

use WellRested\Serializer\Attributes\Field;

class User
{
	public function __construct(
		public string $firstName,
		public string $lastName,
		public string $email,
		public int $age,
		public Address $primaryAddress,
		#[Field(type: Address::class . '[]')]
		public array $secondaryAddresses,
	) {}
}
