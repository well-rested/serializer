<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\Extractors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Unit\Analysis\Extractors\Fixture\DefaultValueFixture;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyDefaultValueExtractor;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(PropertyDefaultValueExtractor::class)]
class PropertyDefaultValueExtractorTest extends TestCase
{
	protected ?PropertyDefaultValueExtractor $extractor;

	public function setUp(): void
	{
		parent::setUp();
		$this->extractor = new PropertyDefaultValueExtractor();
	}

	public function test_non_promoted_with_default(): void
	{
		$prop = new ReflectionProperty(DefaultValueFixture::class, 'withDefault');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', 42)->add('default_exists', true),
			$got,
		);
	}

	public function test_non_promoted_without_default(): void
	{
		$prop = new ReflectionProperty(DefaultValueFixture::class, 'withoutDefault');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', null)->add('default_exists', false),
			$got,
		);
	}

	public function test_promoted_with_default(): void
	{
		$prop = new ReflectionProperty(DefaultValueFixture::class, 'promotedWithDefault');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', 100)->add('default_exists', true),
			$got,
		);
	}

	public function test_promoted_without_default(): void
	{
		$prop = new ReflectionProperty(DefaultValueFixture::class, 'promotedWithoutDefault');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', null)->add('default_exists', false),
			$got,
		);
	}
}
