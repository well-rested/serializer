<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use RuntimeException;

class PolymorphismStrategy
{
	public function __construct(
		protected bool $enabled,
		protected ?string $field = null,
		/** @var array<string, class-string> */
		protected ?array $typeMap = null,
	) {}

	public function enabled(): bool
	{
		return $this->enabled;
	}

	public function field(): string
	{
		if (!$this->enabled) {
			throw new RuntimeException('Polymorphism is not enabled.');
		}

		if ($this->field === null) {
			throw new RuntimeException('Invalid polymorphism strategy, field is not set.');
		}

		return $this->field;
	}

	/** @return array<string, class-string> */
	public function typeMap(): array
	{
		if (!$this->enabled) {
			throw new RuntimeException('Polymorphism is not enabled.');
		}

		if ($this->typeMap === null) {
			throw new RuntimeException('Invalid polymorphism strategy, type map is not set.');
		}

		return $this->typeMap;
	}
}
