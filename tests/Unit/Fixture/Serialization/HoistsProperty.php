<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\Serialization;

use WellRested\Serializer\Attributes\Hoist;

class HoistsProperty
{
	public function __construct(
		#[Hoist('id')]
		public IdValueObject $id,
		#[Hoist(property: 'items')]
		public HoistableThings $hoistedThings,
		public HoistableThings $notHoistedThings,
	) {}
}
