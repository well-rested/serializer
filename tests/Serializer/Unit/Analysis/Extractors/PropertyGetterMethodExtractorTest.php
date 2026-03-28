<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Tests\Serializer\Unit\Analysis\Extractors\Fixture\GetterFixture;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyGetterMethodExtractor;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(PropertyGetterMethodExtractor::class)]
class PropertyGetterMethodExtractorTest extends TestCase
{
	protected ?PropertyGetterMethodExtractor $extractor;

	public function setUp(): void
	{
		parent::setUp();
		$this->extractor = new PropertyGetterMethodExtractor();
	}

	public function test_public_property_returns_public_getter_strategy(): void
	{
		$prop = new ReflectionProperty(GetterFixture::class, 'publicProp');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new GetPropertyStrategy(
				method: GetPropertyStrategyMethod::PublicGetter,
			)),
			$got,
		);
	}

	public function test_private_property_with_valid_get_via_returns_getter_method_strategy(): void
	{
		$prop = new ReflectionProperty(GetterFixture::class, 'privatePropWithValidGetter');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new GetPropertyStrategy(
				method: GetPropertyStrategyMethod::GetterMethod,
				getterMethod: 'getPrivateProp',
			)),
			$got,
		);
	}

	public function test_private_property_without_get_via_throws(): void
	{
		$prop = new ReflectionProperty(GetterFixture::class, 'propWithNoGetter');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('could not find way to get property');

		$this->extractor->extract($prop);
	}

	public function test_get_via_method_not_found_throws(): void
	{
		$prop = new ReflectionProperty(GetterFixture::class, 'propMissingMethod');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('method defined in GetVia attribute not found on class');

		$this->extractor->extract($prop);
	}

	public function test_get_via_method_not_public_throws(): void
	{
		$prop = new ReflectionProperty(GetterFixture::class, 'propWithPrivateGetter');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('method defined in GetVia attribute is not public');

		$this->extractor->extract($prop);
	}

	public function test_get_via_method_with_params_throws(): void
	{
		$prop = new ReflectionProperty(GetterFixture::class, 'propWithGetterWithParams');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('invalid number of args for getter method');

		$this->extractor->extract($prop);
	}

	public function test_get_via_method_return_type_mismatch_throws(): void
	{
		$prop = new ReflectionProperty(GetterFixture::class, 'propWithWrongTypeGetter');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('return type from getter method, must match type of property');

		$this->extractor->extract($prop);
	}
}
