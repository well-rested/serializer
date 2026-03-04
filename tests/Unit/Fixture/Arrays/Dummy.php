<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Arrays;

class Dummy
{
	public function __construct(
		protected int $id,
		protected string $name,
	) {}
}
