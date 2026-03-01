<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

use WellRested\Serializer\Attributes\SetVia;

class SetViaIncorrectArgType
{
	#[SetVia('setSomeString')]
	protected string $someString;

	public function setSomeString(bool $val): void
	{
		$this->someString = 'blah';
	}
}
