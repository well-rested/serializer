<?php

declare(strict_types=1);

namespace WellRested\Serializer\Attributes;

use Attribute;

/**
 * Allows you to override the name of the field as it appears in the API.
 *
 * Also allows for tyoe overriding and narrowing. For example if the type is an
 * array, you can set type: MyObject[] on this attribute so the serializer can
 * build a more accurate spec. The actual string here can be pretty much any valid
 * php typehint, including unions, intersection etc.
 *
 * See: OpenApi/Serialization/Analysis/TypeDefinitions/TypeDefinitionFactory
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Field
{
	public function __construct(
		public ?string $name = null,
		public ?string $type = null,
	) {}
}
