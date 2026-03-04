<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\CastableTypes;

class CastableTypes
{
	public function __construct(
		protected ?string $someString = null,
		protected ?int $someInt = null,
		protected ?float $someFloat = null,
		protected ?bool $someBool = null,
	) {}
}
