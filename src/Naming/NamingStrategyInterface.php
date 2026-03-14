<?php

declare(strict_types=1);

namespace WellRested\Serializer\Naming;

interface NamingStrategyInterface
{
	public function convert(string $before): string;
}
