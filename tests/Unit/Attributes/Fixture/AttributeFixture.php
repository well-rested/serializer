<?php

declare(strict_types=1);

namespace Tests\Unit\Attributes\Fixture;

use WellRested\Serializer\Attributes\Field;
use WellRested\Serializer\Attributes\GetVia;
use WellRested\Serializer\Attributes\Hoist;
use WellRested\Serializer\Attributes\Polymorphic;
use WellRested\Serializer\Attributes\SetVia;
use stdClass;

class AttributeFixture
{
	#[Field(name: 'renamed', type: 'string[]')]
	public mixed $fieldWithBoth = null;

	#[Field(name: 'only_name')]
	public mixed $fieldWithNameOnly = null;

	#[Field(type: 'int')]
	public mixed $fieldWithTypeOnly = null;

	#[Field]
	public mixed $fieldWithDefaults = null;

	#[GetVia('getMyValue')]
	public mixed $getViaProperty = null;

	#[SetVia('setMyValue')]
	public mixed $setViaProperty = null;

	#[Hoist('items')]
	public mixed $hoistProperty = null;

	#[Polymorphic(typeMap: ['cat' => stdClass::class, 'dog' => stdClass::class])]
	public mixed $polymorphicWithDefaults = null;

	#[Polymorphic(typeMap: ['cat' => stdClass::class], field: 'kind')]
	public mixed $polymorphicWithCustomField = null;
}
