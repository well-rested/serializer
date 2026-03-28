<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\Fixture;

use PhpOption\Option;

class PromotedOptionalFieldsB
{
	/** @var Option<string> */
	public Option $line1;

	/** @var Option<string> */
	public Option $line2;
}
