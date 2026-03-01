<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Arrays;

use WellRested\Serializer\Attributes\Field;

class ScalarArrays
{
	public function __construct(
		#[Field(type: 'int[]')]
		protected array $ints,
		#[Field(type: 'string[]')]
		protected array $strings,
		#[Field(type: 'float[]')]
		protected array $floats,
		#[Field(type: 'bool[]')]
		protected array $bools,
		#[Field(type: 'mixed')]
		protected array $mixedValues,
	) {}
}
