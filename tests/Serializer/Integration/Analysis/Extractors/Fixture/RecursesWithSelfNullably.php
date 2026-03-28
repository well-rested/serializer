<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\Fixture;

class RecursesWithSelfNullably
{
	public ?RecursesWithSelfNullably $anotherMe;
}
