<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture;

class PublicPromotedProperties
{
	public function __construct(
		public string $someString,
		public bool $someBool,
		public int $someInt,
	) {}
}
