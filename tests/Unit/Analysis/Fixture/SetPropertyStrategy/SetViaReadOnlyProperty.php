<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

use WellRested\Serializer\Attributes\SetVia;

class SetViaReadOnlyProperty
{
	#[SetVia('setSomeString')]
	protected readonly string $someString;

	public function setSomeString(string $val): void
	{
		$this->someString = $val;
	}
}
