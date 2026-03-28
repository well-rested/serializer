<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use RuntimeException;

class HoistStrategy
{
	public function __construct(
		protected bool $enabled,
		protected ?string $property = null,
	) {}

	public function enabled(): bool
	{
		return $this->enabled;
	}

	public function getProperty(): string
	{
		if (!$this->enabled) {
			throw new RuntimeException('Hoisting is not enabled!');
		}

		if (null === $this->property) {
			throw new RuntimeException('Invalid hoist strategy, property is not set when hoisting is enabled');
		}

		return $this->property;
	}
}
