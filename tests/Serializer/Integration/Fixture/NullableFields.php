<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture;

class NullableFields
{
	public function __construct(
		public ?string $name,
		public ?int $id,
	) {}
}
