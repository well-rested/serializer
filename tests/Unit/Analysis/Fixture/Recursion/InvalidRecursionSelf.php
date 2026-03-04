<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\Recursion;

class InvalidRecursionSelf
{
	public self $anotherMe;
}
