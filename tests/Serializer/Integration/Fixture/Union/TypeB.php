<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture\Union;

class TypeB
{
	public function __construct(
		public string $name,
	) {}
}
