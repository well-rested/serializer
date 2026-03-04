<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\FieldAttribute;

use WellRested\Serializer\Attributes\Field;

class SettingTypeViaFieldAttr
{
	#[Field(type: 'string[]')]
	public array $someStrings;

	#[Field(type: '(int|string)[]')]
	public array $someDates;
}
