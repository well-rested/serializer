<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NullableFields;

class WithNullableFields
{
	public function __construct(
		protected ?string $name,
		protected ?int $id,
	) {}
}
