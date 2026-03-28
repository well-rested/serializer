<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Serializer\Unit\Attributes\Fixture\AttributeFixture;
use WellRested\Serializer\Attributes\SetVia;

#[CoversClass(SetVia::class)]
class SetViaTest extends TestCase
{
	public function test_method_is_set(): void
	{
		$attr = new SetVia('setMyValue');

		$this->assertSame('setMyValue', $attr->method);
	}

	public function test_readable_from_property_reflection(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'setViaProperty'))
			->getAttributes(SetVia::class)[0]
			->newInstance();

		$this->assertSame('setMyValue', $attr->method);
	}
}
