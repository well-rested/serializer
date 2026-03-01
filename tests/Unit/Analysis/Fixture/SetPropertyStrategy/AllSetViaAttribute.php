<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

use WellRested\Serializer\Attributes\SetVia;

class AllSetViaAttribute
{
	#[SetVia('setSomeString')]
	protected string $someString;

	#[SetVia('setSomeBool')]
	protected bool $someBool;

	#[SetVia('setSomeInt')]
	private int $someInt;

	public function setSomeString(string $val): void
	{
		$this->someString = $val;
	}

	public function setSomeBool(bool $val): void
	{
		$this->someBool = $val;
	}

	public function setSomeInt(int $val): void
	{
		$this->someInt = $val;
	}
}
