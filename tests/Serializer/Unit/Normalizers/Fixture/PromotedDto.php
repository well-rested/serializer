<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Normalizers\Fixture;

class PromotedDto
{
	public function __construct(
		public int $age,
	) {}
}
