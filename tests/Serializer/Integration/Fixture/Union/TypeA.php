<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture\Union;

class TypeA
{
	public function __construct(
		public string $name,
	) {}
}
