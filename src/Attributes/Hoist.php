<?php

declare(strict_types=1);

namespace WellRested\Serializer\Attributes;

use Attribute;

/**
 * This attribute tells the serializer to hoist the value from a subproperty.
 * Easiest to explain with an example, but a good use-case is for collection
 * classes where in api structure it's just an array, but in php it's an object
 * that contains a property that is an array.
 *
 * @see: OpenApi/Symfony/Responses/ValidationErrorResponse
 *
 * Without hoists this would be serialized as something like:
 * {
 *     "message": "some message",
 *     "errors": {
 *         "items": [
 *             {"my error"...},
 *             {"my error"...},
 *         ]
 *     }
 * }
 *
 * But we hoist the items property, so it becomes:
 *
 * {
 *     "message": "some message",
 *     "errors": [
 *         {"my error"...},
 *         {"my error"...},
 *     ]
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Hoist
{
	public function __construct(
		public string $property,
	) {}
}
