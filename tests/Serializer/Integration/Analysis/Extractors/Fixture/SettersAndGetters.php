<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\Fixture;

use WellRested\Serializer\Attributes\GetVia;
use WellRested\Serializer\Attributes\SetVia;

class SettersAndGetters
{
	public int $myInt = 1234;

	#[SetVia(method: 'setMyString')]
	#[GetVia(method: 'getMyString')]
	protected string $myString = 'meh';

	public function __construct(
		#[GetVia(method: 'getId')]
		protected string $id = 'blah',
	) {}

	public function getId(): string
	{
		return $this->id;
	}

	public function setMyString(string $val): void
	{
		$this->myString = $val;
	}

	public function getMyString(): string
	{
		return $this->myString;
	}
}
