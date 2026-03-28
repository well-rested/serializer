<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tests\Serializer\Unit\Analysis\Extractors\Fixture\Hoist\HoistFixture;
use WellRested\Serializer\Analysis\Extractors\Extensions\HoistStrategyExtractor;
use WellRested\Serializer\Analysis\HoistStrategy;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(HoistStrategyExtractor::class)]
class HoistStrategyExtractorTest extends TestCase
{
	protected Reflector|MockObject|null $reflector;

	protected ?HoistStrategyExtractor $extractor;

	public function setUp(): void
	{
		parent::setUp();

		$this->reflector = $this->createMock(Reflector::class);
		$this->extractor = new HoistStrategyExtractor($this->reflector);
	}

	public function test_no_hoist_attribute(): void
	{
		$this->reflector->expects($this->never())
			->method('getPropertyType');

		$prop = new ReflectionProperty(HoistFixture::class, 'propertyWithoutHoist');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new HoistStrategy(
				enabled: false,
				property: null,
			)),
			$got,
		);
	}

	public function test_valid_hoist(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->with(HoistFixture::class, 'someProp')
			->willReturn(new ObjectType(stdClass::class));

		$prop = new ReflectionProperty(HoistFixture::class, 'propertyWithHoist');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new HoistStrategy(
				enabled: true,
				property: 'someProp',
			)),
			$got,
		);
	}

	public function test_hoist_on_non_object(): void
	{
		$this->reflector->expects($this->once())
			->method('getPropertyType')
			->with(HoistFixture::class, 'someProp')
			->willReturn(new BuiltinType(TypeIdentifier::INT));

		$prop = new ReflectionProperty(HoistFixture::class, 'propertyWithHoist');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(
			'cannot hoist a property from a non-object in: ' . HoistFixture::class . '->propertyWithHoist',
		);

		$this->extractor->extract($prop);
	}
}
