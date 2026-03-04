<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Options;

use WellRested\Serializer\Attributes as OA;
use PhpOption\Option;

class OptionalPromotedFields
{
	public function __construct(
		#[OA\Field(type: 'string')]
		protected Option $email,
		#[OA\Field(type: 'string')]
		protected Option $firstName,
		#[OA\Field(type: 'string')]
		protected Option $lastName,
		#[OA\Field(type: SubField::class)]
		protected Option $sub,
	) {}
}
