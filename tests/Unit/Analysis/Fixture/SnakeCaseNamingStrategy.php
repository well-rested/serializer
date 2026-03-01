<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture;

use WellRested\Serializer\NamingStrategyInterface;

use function Symfony\Component\String\u;

class SnakeCaseNamingStrategy implements NamingStrategyInterface
{
	public function convert(string $value): string
	{
		return (string) u($value)->snake();
	}
}
