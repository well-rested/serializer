<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\Defaults;

use WellRested\Serializer\Attributes\SetVia;

class HasDefaults
{
	public int $myInt = 1234;

	#[SetVia(method: 'setMyString')]
	protected string $myString = 'meh';

	public function __construct(
		protected string $id = 'blah',
	) {}

	public function setMyString(string $val): void
	{
		$this->myString = $val;
	}
}
