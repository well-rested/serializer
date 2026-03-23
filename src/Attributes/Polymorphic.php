<?php

declare(strict_types=1);

namespace WellRested\Serializer\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * TODO: explain...
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Polymorphic
{
	public const DEFAULT_FIELD = '@type';

	public function __construct(
		/** @var array<string, class-string> */
		public array $typeMap,
		public string $field = self::DEFAULT_FIELD,
	) {
		foreach ($this->typeMap as $key => $value) {
			if (! is_string($key)) {
				throw new InvalidArgumentException('keys in typemap must be strings');
			}
		}
	}
}
