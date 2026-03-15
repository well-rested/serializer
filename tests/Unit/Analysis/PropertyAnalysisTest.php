<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Analysis\Extractors\Extensions\HoistStrategyExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyDefaultValueExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyGetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertySetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\SerializedPropertyNameExtractor;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysis;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysisExtractionExtensions;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Analysis\HoistStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Util\MixedDictionary;

#[CoversClass(PropertyAnalysis::class)]
class PropertyAnalysisTest extends TestCase
{
	public function test_basic_getters()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: new PropertyAnalysisExtractionExtensions(),
		);

		$this->assertEquals('someProp', $analysis->getName());
		$this->assertEquals(new BuiltinType(TypeIdentifier::INT), $analysis->getType());
	}

	public function test_get_default_value_when_extension_not_present()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions()),
		);

		$this->assertEquals(None::create(), $analysis->getDefaultValue());
	}

	public function test_get_default_value_when_extension_dict_is_empty()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(PropertyDefaultValueExtractor::EXTENSION_NAME, new MixedDictionary()),
		);

		$this->assertEquals(None::create(), $analysis->getDefaultValue());
	}

	public function test_get_default_value_default_exists_is_false()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: PropertyDefaultValueExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('default_exists', false),
				),
		);

		$this->assertEquals(None::create(), $analysis->getDefaultValue());
	}

	public function test_get_default_value_default_exists_is_true_but_no_value()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: PropertyDefaultValueExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('default_exists', true),
				),
		);

		$this->assertEquals(None::create(), $analysis->getDefaultValue());
	}

	public function test_get_default_value_default_exists_is_true_and_value_is_present()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: PropertyDefaultValueExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('default_exists', true)
						->add('value', 'somevalue'),
				),
		);

		$this->assertEquals(Some::create('somevalue'), $analysis->getDefaultValue());
	}

	// getSerializedPropertyName

	public function test_get_serialized_property_name_when_extension_not_present()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: new PropertyAnalysisExtractionExtensions(),
		);

		$this->assertEquals('someProp', $analysis->getSerializedPropertyName());
	}

	public function test_get_serialized_property_name_when_value_is_not_a_string()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: SerializedPropertyNameExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', 99),
				),
		);

		$this->assertEquals('someProp', $analysis->getSerializedPropertyName());
	}

	public function test_get_serialized_property_name_when_value_is_a_string()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: SerializedPropertyNameExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', 'some_prop'),
				),
		);

		$this->assertEquals('some_prop', $analysis->getSerializedPropertyName());
	}

	// getSetterStrategy

	public function test_get_setter_strategy_when_extension_not_present()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: new PropertyAnalysisExtractionExtensions(),
		);

		$this->assertEquals(
			new SetPropertyStrategy(method: SetPropertyStrategyMethod::NotAvailable),
			$analysis->getSetterStrategy(),
		);
	}

	public function test_get_setter_strategy_when_value_is_not_a_set_property_strategy()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: PropertySetterMethodExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', 'not-a-strategy'),
				),
		);

		$this->assertEquals(
			new SetPropertyStrategy(method: SetPropertyStrategyMethod::NotAvailable),
			$analysis->getSetterStrategy(),
		);
	}

	public function test_get_setter_strategy_returns_strategy_when_present()
	{
		$strategy = new SetPropertyStrategy(
			method: SetPropertyStrategyMethod::SetterMethod,
			setterMethod: 'setSomeProp',
		);

		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: PropertySetterMethodExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', $strategy),
				),
		);

		$this->assertEquals($strategy, $analysis->getSetterStrategy());
	}

	// getGetterStrategy

	public function test_get_getter_strategy_when_extension_not_present()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: new PropertyAnalysisExtractionExtensions(),
		);

		$this->assertEquals(
			new GetPropertyStrategy(method: GetPropertyStrategyMethod::NotAvailable),
			$analysis->getGetterStrategy(),
		);
	}

	public function test_get_getter_strategy_when_value_is_not_a_get_property_strategy()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: PropertyGetterMethodExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', 'not-a-strategy'),
				),
		);

		$this->assertEquals(
			new GetPropertyStrategy(method: GetPropertyStrategyMethod::NotAvailable),
			$analysis->getGetterStrategy(),
		);
	}

	public function test_get_getter_strategy_returns_strategy_when_present()
	{
		$strategy = new GetPropertyStrategy(
			method: GetPropertyStrategyMethod::GetterMethod,
			getterMethod: 'getSomeProp',
		);

		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: PropertyGetterMethodExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', $strategy),
				),
		);

		$this->assertEquals($strategy, $analysis->getGetterStrategy());
	}

	// getHoistStrategy

	public function test_get_hoist_strategy_when_extension_not_present()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: new PropertyAnalysisExtractionExtensions(),
		);

		$this->assertEquals(
			new HoistStrategy(enabled: false),
			$analysis->getHoistStrategy(),
		);
	}

	public function test_get_hoist_strategy_when_value_is_not_a_hoist_strategy()
	{
		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: HoistStrategyExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', 'not-a-strategy'),
				),
		);

		$this->assertEquals(
			new HoistStrategy(enabled: false),
			$analysis->getHoistStrategy(),
		);
	}

	public function test_get_hoist_strategy_returns_strategy_when_present()
	{
		$strategy = new HoistStrategy(enabled: true, property: 'items');

		$analysis = new PropertyAnalysis(
			name: 'someProp',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					key: HoistStrategyExtractor::EXTENSION_NAME,
					item: (new MixedDictionary())
						->add('value', $strategy),
				),
		);

		$this->assertEquals($strategy, $analysis->getHoistStrategy());
	}
}
