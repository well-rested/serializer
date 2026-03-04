<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\FieldAttribute;

use WellRested\Serializer\Attributes\Field;

class OverridesSerializedNames
{
	#[Field(name: 'super_special_int')]
	public int $someInt;

	#[Field(name: 'someRandomString')]
	public string $someString;

	#[Field(name: 'complete_garbage_name')]
	public string $someOtherString;
}
