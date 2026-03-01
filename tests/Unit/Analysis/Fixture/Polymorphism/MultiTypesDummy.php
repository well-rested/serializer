<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\Polymorphism;

use Tests\Unit\Analysis\Fixture\SimpleDummy;

class MultiTypesDummy
{
	public string|SimpleDummy $union;

	public DummyInterface&SimpleDummy $intersection;
}
