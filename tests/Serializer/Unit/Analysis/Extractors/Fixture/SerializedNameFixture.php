<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors\Fixture;

use WellRested\Serializer\Attributes\Field;

class SerializedNameFixture
{
	public int $myProperty;

	#[Field(name: 'custom_name')]
	public int $namedProperty;
}
