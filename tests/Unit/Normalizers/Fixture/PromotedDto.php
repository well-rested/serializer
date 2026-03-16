<?php

declare(strict_types=1);

namespace Tests\Unit\Normalizers\Fixture;

class PromotedDto
{
	public function __construct(
		public int $age,
	) {}
}
