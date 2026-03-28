<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Attributes;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Serializer\Unit\Attributes\Fixture\AttributeFixture;
use WellRested\Serializer\Attributes\Polymorphic;
use stdClass;

#[CoversClass(Polymorphic::class)]
class PolymorphicTest extends TestCase
{
	public function test_field_defaults_to_at_type(): void
	{
		$attr = new Polymorphic(typeMap: ['foo' => stdClass::class]);

		$this->assertSame(Polymorphic::DEFAULT_FIELD, $attr->field);
		$this->assertSame('@type', $attr->field);
	}

	public function test_type_map_is_set(): void
	{
		$typeMap = ['cat' => stdClass::class, 'dog' => stdClass::class];
		$attr = new Polymorphic(typeMap: $typeMap);

		$this->assertSame($typeMap, $attr->typeMap);
	}

	public function test_custom_field_is_set(): void
	{
		$attr = new Polymorphic(typeMap: ['foo' => stdClass::class], field: 'kind');

		$this->assertSame('kind', $attr->field);
	}

	public function test_throws_when_type_map_has_non_string_keys(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new Polymorphic(typeMap: [0 => stdClass::class]);
	}

	public function test_readable_from_property_reflection_with_defaults(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'polymorphicWithDefaults'))
			->getAttributes(Polymorphic::class)[0]
			->newInstance();

		$this->assertSame(['cat' => stdClass::class, 'dog' => stdClass::class], $attr->typeMap);
		$this->assertSame('@type', $attr->field);
	}

	public function test_readable_from_property_reflection_with_custom_field(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'polymorphicWithCustomField'))
			->getAttributes(Polymorphic::class)[0]
			->newInstance();

		$this->assertSame(['cat' => stdClass::class], $attr->typeMap);
		$this->assertSame('kind', $attr->field);
	}
}
