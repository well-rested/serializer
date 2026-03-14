<?php

declare(strict_types=1);

namespace Tests\Unit\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Unit\Attributes\Fixture\AttributeFixture;
use WellRested\Serializer\Attributes\Hoist;

#[CoversClass(Hoist::class)]
class HoistTest extends TestCase
{
	public function test_property_is_set(): void
	{
		$attr = new Hoist('items');

		$this->assertSame('items', $attr->property);
	}

	public function test_readable_from_property_reflection(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'hoistProperty'))
			->getAttributes(Hoist::class)[0]
			->newInstance();

		$this->assertSame('items', $attr->property);
	}
}
