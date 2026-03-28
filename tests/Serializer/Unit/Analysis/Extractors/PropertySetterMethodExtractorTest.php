<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Extractors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Tests\Serializer\Unit\Analysis\Extractors\Fixture\PromotedSetterFixture;
use Tests\Serializer\Unit\Analysis\Extractors\Fixture\ReadonlySetterFixture;
use Tests\Serializer\Unit\Analysis\Extractors\Fixture\SetterFixture;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertySetterMethodExtractor;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(PropertySetterMethodExtractor::class)]
class PropertySetterMethodExtractorTest extends TestCase
{
	protected ?PropertySetterMethodExtractor $extractor;

	public function setUp(): void
	{
		parent::setUp();
		$this->extractor = new PropertySetterMethodExtractor();
	}

	public function test_public_property_returns_public_setter_strategy(): void
	{
		$prop = new ReflectionProperty(SetterFixture::class, 'publicProp');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::PublicSetter,
			)),
			$got,
		);
	}

	public function test_valid_set_via_returns_setter_method_strategy(): void
	{
		$prop = new ReflectionProperty(SetterFixture::class, 'propWithValidSetter');
		$got = $this->extractor->extract($prop);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::SetterMethod,
				setterMethod: 'setPrivateProp',
			)),
			$got,
		);
	}

	public function test_promoted_property_returns_constructor_argument_with_correct_index(): void
	{
		$firstProp = new ReflectionProperty(PromotedSetterFixture::class, 'firstProp');
		$got = $this->extractor->extract($firstProp);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::ConstructorArgument,
				constructorIndex: 0,
			)),
			$got,
		);

		$secondProp = new ReflectionProperty(PromotedSetterFixture::class, 'secondProp');
		$got = $this->extractor->extract($secondProp);

		$this->assertEquals(
			(new MixedDictionary())->add('value', new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::ConstructorArgument,
				constructorIndex: 1,
			)),
			$got,
		);
	}

	public function test_readonly_property_throws(): void
	{
		$prop = new ReflectionProperty(ReadonlySetterFixture::class, 'readonlyProp');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('property or class is readonly so all properties must be promoted via constructor');

		$this->extractor->extract($prop);
	}

	public function test_non_public_non_promoted_without_set_via_throws(): void
	{
		$prop = new ReflectionProperty(SetterFixture::class, 'propWithNoSetter');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('could not determine viable setter method for property');

		$this->extractor->extract($prop);
	}

	public function test_set_via_method_not_found_throws(): void
	{
		$prop = new ReflectionProperty(SetterFixture::class, 'propMissingSetter');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('method defined in SetVia attribute not found on class');

		$this->extractor->extract($prop);
	}

	public function test_set_via_method_not_public_throws(): void
	{
		$prop = new ReflectionProperty(SetterFixture::class, 'propWithPrivateSetter');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('method defined in SetVia attribute is not public');

		$this->extractor->extract($prop);
	}

	public function test_set_via_method_wrong_param_count_throws(): void
	{
		$prop = new ReflectionProperty(SetterFixture::class, 'propSetterNoParams');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('invalid number of args for setter method');

		$this->extractor->extract($prop);
	}

	public function test_set_via_method_param_type_mismatch_throws(): void
	{
		$prop = new ReflectionProperty(SetterFixture::class, 'propSetterWrongType');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('only argument to setter method, must match type of property');

		$this->extractor->extract($prop);
	}
}
