<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

use WellRested\Serializer\Attributes\SetVia;

class SetViaNonPublicMethod
{
	#[SetVia('setSomeString')]
	protected string $someString;

	protected function setSomeString(string $val): void
	{
		$this->someString = $val;
	}
}
