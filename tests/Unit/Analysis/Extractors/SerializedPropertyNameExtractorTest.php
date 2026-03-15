<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Extractors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Unit\Analysis\Extractors\Fixture\SerializedNameFixture;
use WellRested\Serializer\Analysis\Extractors\Extensions\SerializedPropertyNameExtractor;
use WellRested\Serializer\Naming\NamingStrategyInterface;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(SerializedPropertyNameExtractor::class)]
class SerializedPropertyNameExtractorTest extends TestCase
{
	protected NamingStrategyInterface|MockObject|null $namingStrategy;

	protected ?SerializedPropertyNameExtractor $extractor;

	public function setUp(): void
	{
		parent::setUp();
		$this->namingStrategy = $this->createMock(NamingStrategyInterface::class);
		$this->extractor = new SerializedPropertyNameExtractor($this->namingStrategy);
	}

	public function test_no_field_attribute_uses_naming_strategy(): void
	{
		$this->namingStrategy->expects($this->once())
			->method('convert')
			->with('myProperty')
			->willReturn('my_property');

		$prop = new ReflectionProperty(SerializedNameFixture::class, 'myProperty');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', 'my_property'),
			$got,
		);
	}

	public function test_field_attribute_with_name_overrides_naming_strategy(): void
	{
		$this->namingStrategy->expects($this->never())
			->method('convert');

		$prop = new ReflectionProperty(SerializedNameFixture::class, 'namedProperty');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', 'custom_name'),
			$got,
		);
	}
}
