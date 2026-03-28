<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture;

class DefaultValues
{
	public function __construct(
		public string $name = "josephus",
		public int $id = 54,
	) {}
}
