<?php

declare(strict_types=1);

namespace WellRested\Serializer\Attributes;

use Attribute;

/**
 * Tells the serializer how to get the property off of an object. Generally it
 * will lean towards public accessors (i.e. $object->myProperty). But if they're
 * not possible (it's protected or private), then you can use this attribute to
 * tell the serializer to get the value of a property via the given method.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class GetVia
{
	public function __construct(
		public string $method,
	) {}
}
