<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

use WellRested\Serializer\Attributes\SetVia;

class SetViaTooManyArgs
{
	#[SetVia('setSomeString')]
	protected string $someString;

	public function setSomeString(string $arg1, $arg2): void
	{
		$this->someString = $arg1;
	}
}
