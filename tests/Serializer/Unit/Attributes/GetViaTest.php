<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Serializer\Unit\Attributes\Fixture\AttributeFixture;
use WellRested\Serializer\Attributes\GetVia;

#[CoversClass(GetVia::class)]
class GetViaTest extends TestCase
{
	public function test_method_is_set(): void
	{
		$attr = new GetVia('getMyValue');

		$this->assertSame('getMyValue', $attr->method);
	}

	public function test_readable_from_property_reflection(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'getViaProperty'))
			->getAttributes(GetVia::class)[0]
			->newInstance();

		$this->assertSame('getMyValue', $attr->method);
	}
}
