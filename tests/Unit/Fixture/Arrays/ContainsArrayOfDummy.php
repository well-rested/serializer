<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Arrays;

use WellRested\Serializer\Attributes\Field;

class ContainsArrayOfDummy
{
	public function __construct(
		#[Field(type: Dummy::class . '[]')]
		protected array $dummies,
	) {}
}
