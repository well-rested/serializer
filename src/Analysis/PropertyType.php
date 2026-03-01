<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

class PropertyType
{
	public function __construct(
		protected string $name,
		protected ?string $type,
	) {}
}
