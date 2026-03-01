<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use function Symfony\Component\String\u;

class SnakeCaseNamingStrategy implements NamingStrategyInterface
{
	public function convert(string $value): string
	{
		return (string) u($value)->snake();
	}
}
