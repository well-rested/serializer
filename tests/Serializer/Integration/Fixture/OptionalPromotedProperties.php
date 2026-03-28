<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Fixture;

use PhpOption\Option;

class OptionalPromotedProperties
{
	public function __construct(
		/** @var Option<string> */
		public Option $someString,
		/** @var Option<bool> */
		public Option $someBool,
		/** @var Option<int> */
		public Option $someInt,
	) {}
}
