<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors\Fixture;

class ReadonlySetterFixture
{
	public readonly int $readonlyProp;

	public function __construct(int $readonlyProp)
	{
		$this->readonlyProp = $readonlyProp;
	}
}
