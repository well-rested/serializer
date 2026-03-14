<?php

declare(strict_types=1);

namespace Tests\Integration\Analysis\Extractors\Fixture;

use PhpOption\Option;

class PromotedOptionalFieldsA
{
	public function __construct(
		/** @var Option<string> */
		public Option $email,
		/** @var Option<string> */
		public Option $firstName,
		/** @var Option<string> */
		public Option $lastName,
		/** @var Option<PromotedOptionalFieldsB> */
		public Option $sub,
	) {}
}
