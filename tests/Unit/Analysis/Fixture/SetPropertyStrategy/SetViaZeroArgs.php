<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

use WellRested\Serializer\Attributes\SetVia;

class SetViaZeroArgs
{
	#[SetVia('setSomeString')]
	protected string $someString;

	public function setSomeString(): void
	{
		$this->someString = 'blah';
	}
}
