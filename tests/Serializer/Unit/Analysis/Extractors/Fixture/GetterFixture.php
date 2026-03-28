<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors\Fixture;

use WellRested\Serializer\Attributes\GetVia;

class GetterFixture
{
	public int $publicProp;

	#[GetVia('getPrivateProp')]
	private int $privatePropWithValidGetter;

	public function getPrivateProp(): int
	{
		return $this->privatePropWithValidGetter;
	}

	#[GetVia('nonExistentMethod')]
	private int $propMissingMethod;

	#[GetVia('privateGetter')]
	private int $propWithPrivateGetter;

	private function privateGetter(): int
	{
		return $this->propWithPrivateGetter;
	}

	#[GetVia('getterWithParams')]
	private int $propWithGetterWithParams;

	public function getterWithParams(int $x): int
	{
		return $x;
	}

	#[GetVia('wrongTypeGetter')]
	private int $propWithWrongTypeGetter;

	public function wrongTypeGetter(): string
	{
		return '';
	}

	private int $propWithNoGetter;
}
