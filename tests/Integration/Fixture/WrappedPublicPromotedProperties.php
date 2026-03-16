<?php

declare(strict_types=1);

namespace Tests\Integration\Fixture;

use WellRested\Serializer\Attributes\Wrap;

class WrappedPublicPromotedProperties
{
	public function __construct(
		#[Wrap('data')]
		public PublicPromotedProperties $body,
	) {}
}
