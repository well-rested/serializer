<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

class WrappingStrategy
{
	public function __construct(
		protected bool $enabled,
		protected ?string $key = null,
	) {}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function getKey(): ?string
	{
		return $this->key;
	}
}
