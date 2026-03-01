<?php

declare(strict_types=1);

namespace WellRested\Serializer\Attributes;

use Attribute;

/**
 * This can be used to tell the serializer how to set the property on the given
 * object. It will favour setying via constructor property promotion or public
 * setters, but if these can't be used, then you can define a method here that
 * can be used to set the value.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class SetVia
{
	public function __construct(
		public string $method,
	) {}
}
