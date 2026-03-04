<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Options;

use WellRested\Serializer\Attributes as OA;
use PhpOption\Option;

class OptionalFields
{
	#[OA\Field(type: 'string')]
	public Option $email;

	#[OA\Field(type: 'string')]
	public Option $firstName;

	#[OA\Field(type: 'string')]
	public Option $lastName;

	#[OA\Field(type: SubField::class)]
	public Option $sub;
}
