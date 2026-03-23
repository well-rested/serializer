<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Extractors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tests\Unit\Analysis\Extractors\Fixture\Polymorphism\AbstractPolymorphic;
use Tests\Unit\Analysis\Extractors\Fixture\Polymorphism\ConcretePolymorphicA;
use Tests\Unit\Analysis\Extractors\Fixture\Polymorphism\ConcretePolymorphicB;
use Tests\Unit\Analysis\Extractors\Fixture\Polymorphism\PolymorphicInterface;
use Tests\Unit\Analysis\Extractors\Fixture\Polymorphism\PolymorphismFixture;
use WellRested\Serializer\Analysis\Extractors\Extensions\PolymorphismExtractor;
use WellRested\Serializer\Analysis\PolymorphismStrategy;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(PolymorphismExtractor::class)]
class PolymorphismExtractorTest extends TestCase
{
	protected Reflector|MockObject $reflector;

	protected PolymorphismExtractor $extractor;

	public function setUp(): void
	{
		parent::setUp();

		$this->reflector = $this->createMock(Reflector::class);
		$this->extractor = new PolymorphismExtractor($this->reflector);
	}

	public function test_extract_returns_disabled_for_non_union_type(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->with(PolymorphismFixture::class, 'nonUnionProperty')
			->willReturn(new BuiltinType(TypeIdentifier::MIXED));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'nonUnionProperty');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new PolymorphismStrategy(
				enabled: false,
			)),
			$got,
		);
	}

	public function test_extract_returns_disabled_when_union_contains_non_object_type(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(ConcretePolymorphicA::class),
				new BuiltinType(TypeIdentifier::INT),
			));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'nonUnionProperty');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new PolymorphismStrategy(
				enabled: false,
			)),
			$got,
		);
	}

	public function test_extract_returns_disabled_when_no_polymorphic_attribute(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(ConcretePolymorphicA::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'unionWithoutAttribute');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new PolymorphismStrategy(
				enabled: false,
			)),
			$got,
		);
	}

	public function test_extract_throws_when_type_map_count_mismatches_union(): void
	{
		// Union has 2 types, but the Polymorphic attribute's typeMap has 1 entry
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(ConcretePolymorphicA::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'countMismatch');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('type map must contain exactly one entry for each union case');

		$this->extractor->extract($prop);
	}

	public function test_extract_throws_when_type_map_contains_abstract_class(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(AbstractPolymorphic::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$this->reflector->expects($this->once())
			->method('reflectClass')
			->with(AbstractPolymorphic::class)
			->willReturn(new ReflectionClass(AbstractPolymorphic::class));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'abstractInTypeMap');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('interface, trait, or abstract');

		$this->extractor->extract($prop);
	}

	public function test_extract_throws_when_type_map_contains_interface(): void
	{
		// Union contains only concrete classes, but typeMap's first entry is an interface
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(ConcretePolymorphicA::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$this->reflector->expects($this->once())
			->method('reflectClass')
			->with(PolymorphicInterface::class)
			->willReturn(new ReflectionClass(PolymorphicInterface::class));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'interfaceInTypeMap');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('interface, trait, or abstract');

		$this->extractor->extract($prop);
	}

	public function test_extract_throws_when_union_contains_interface(): void
	{
		// A union containing interface types passes isValidUnion (interfaces are ObjectType),
		// but throws when the typeMap maps to that interface
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(PolymorphicInterface::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$this->reflector->expects($this->once())
			->method('reflectClass')
			->with(PolymorphicInterface::class)
			->willReturn(new ReflectionClass(PolymorphicInterface::class));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'interfaceInTypeMap');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('interface, trait, or abstract');

		$this->extractor->extract($prop);
	}

	public function test_extract_throws_when_type_map_class_not_in_union(): void
	{
		// Union contains A|B, typeMap maps 'a' => A and 'x' => stdClass (not in union)
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(ConcretePolymorphicA::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$this->reflector->method('reflectClass')
			->willReturnCallback(fn(string $class) => new ReflectionClass($class));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'classNotInUnion');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('type map contains mapping for class which is not in the union');

		$this->extractor->extract($prop);
	}

	public function test_extract_returns_valid_polymorphic_config(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(ConcretePolymorphicA::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$this->reflector->method('reflectClass')
			->willReturnCallback(fn(string $class) => new ReflectionClass($class));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'validPolymorphic');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new PolymorphismStrategy(
				enabled: true,
				field: '@type',
				typeMap: ['a' => ConcretePolymorphicA::class, 'b' => ConcretePolymorphicB::class],
			)),
			$got,
		);
	}

	public function test_extract_returns_valid_config_with_custom_field(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->willReturn(new UnionType(
				new ObjectType(ConcretePolymorphicA::class),
				new ObjectType(ConcretePolymorphicB::class),
			));

		$this->reflector->method('reflectClass')
			->willReturnCallback(fn(string $class) => new ReflectionClass($class));

		$prop = new ReflectionProperty(PolymorphismFixture::class, 'customField');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new PolymorphismStrategy(
				enabled: true,
				field: 'kind',
				typeMap: ['a' => ConcretePolymorphicA::class, 'b' => ConcretePolymorphicB::class],
			)),
			$got,
		);
	}
}
