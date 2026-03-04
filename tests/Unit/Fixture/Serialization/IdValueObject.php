<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Serialization;

class IdValueObject
{
	public function __construct(
		public string $id,
	) {}
}
