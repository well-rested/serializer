<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

use WellRested\Serializer\Attributes\SetVia;

class SetViaMissingMethod
{
	#[SetVia('missingMethod')]
	protected string $someString;
}
