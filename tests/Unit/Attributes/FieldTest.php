<?php

declare(strict_types=1);

namespace Tests\Unit\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Unit\Attributes\Fixture\AttributeFixture;
use WellRested\Serializer\Attributes\Field;

#[CoversClass(Field::class)]
class FieldTest extends TestCase
{
	public function test_name_and_type_default_to_null(): void
	{
		$field = new Field();

		$this->assertNull($field->name);
		$this->assertNull($field->type);
	}

	public function test_name_is_set(): void
	{
		$field = new Field(name: 'my_field');

		$this->assertSame('my_field', $field->name);
	}

	public function test_type_is_set(): void
	{
		$field = new Field(type: 'string[]');

		$this->assertSame('string[]', $field->type);
	}

	public function test_readable_from_property_reflection_with_both_args(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'fieldWithBoth'))
			->getAttributes(Field::class)[0]
			->newInstance();

		$this->assertSame('renamed', $attr->name);
		$this->assertSame('string[]', $attr->type);
	}

	public function test_readable_from_property_reflection_with_name_only(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'fieldWithNameOnly'))
			->getAttributes(Field::class)[0]
			->newInstance();

		$this->assertSame('only_name', $attr->name);
		$this->assertNull($attr->type);
	}

	public function test_readable_from_property_reflection_with_type_only(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'fieldWithTypeOnly'))
			->getAttributes(Field::class)[0]
			->newInstance();

		$this->assertNull($attr->name);
		$this->assertSame('int', $attr->type);
	}

	public function test_readable_from_property_reflection_with_defaults(): void
	{
		$attr = (new ReflectionProperty(AttributeFixture::class, 'fieldWithDefaults'))
			->getAttributes(Field::class)[0]
			->newInstance();

		$this->assertNull($attr->name);
		$this->assertNull($attr->type);
	}
}
