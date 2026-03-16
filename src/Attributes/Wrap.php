<?php

declare(strict_types=1);

namespace WellRested\Serializer\Attributes;

use Attribute;

/**
 * This can be used to wrap the given property inside the given key. The value
 * of the property will be assigned to or pulled from the given key.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Wrap
{
	public function __construct(
		public string $key,
	) {}
}
