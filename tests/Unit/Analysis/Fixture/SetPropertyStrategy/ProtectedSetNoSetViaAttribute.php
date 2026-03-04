<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

class ProtectedSetNoSetViaAttribute
{
	/** @php-cs-fixer-ignore */
	public protected(set) string $someString;
}
