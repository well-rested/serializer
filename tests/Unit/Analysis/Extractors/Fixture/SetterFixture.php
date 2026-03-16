<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Extractors\Fixture;

use WellRested\Serializer\Attributes\SetVia;

class SetterFixture
{
	#[SetVia('setPrivateProp')]
	private int $propWithValidSetter;

	public function setPrivateProp(int $value): void
	{
		$this->propWithValidSetter = $value;
	}

	public int $publicProp;

	private int $propWithNoSetter;

	#[SetVia('nonExistentSetter')]
	private int $propMissingSetter;

	#[SetVia('privateSetter')]
	private int $propWithPrivateSetter;

	private function privateSetter(int $value): void
	{
		$this->propWithPrivateSetter = $value;
	}

	#[SetVia('setterWithNoParams')]
	private int $propSetterNoParams;

	public function setterWithNoParams(): void {}

	#[SetVia('setterWithWrongType')]
	private int $propSetterWrongType;

	public function setterWithWrongType(string $value): void {}
}
