<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Options;

use WellRested\Serializer\Attributes as OA;
use PhpOption\Option;

class SubField
{
	#[OA\Field(type: 'string')]
	public Option $line1;

	#[OA\Field(type: 'string')]
	public Option $line2;
}
