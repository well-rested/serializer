<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Fixture\SetPropertyStrategy;

class PrivateSetNoSetViaAttribute
{
	/** @php-cs-fixer-ignore */
	public private(set) string $someString;
}
