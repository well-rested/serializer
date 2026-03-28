<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\ClassAnalysisScenarios;

use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\SettersAndGetters as Subject;
use WellRested\Serializer\Analysis\Extractors\ClassAnalyses;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysis;
use WellRested\Serializer\Analysis\Extractors\Extensions\ExtendsPropertyExtraction;
use WellRested\Serializer\Analysis\Extractors\Extensions\HoistStrategyExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyDefaultValueExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyGetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertySetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\SerializedPropertyNameExtractor;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalyses;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysis;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysisExtractionExtensions;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Analysis\HoistStrategy;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Util\MixedDictionary;
use WellRested\Serializer\Naming\SnakeCaseNamingStrategy;

class SettersAndGetters
{
	/** @return ExtendsPropertyExtraction[] */
	public function propertyExtractors(): array
	{
		return [
			new PropertyDefaultValueExtractor(),
			new SerializedPropertyNameExtractor(
				new SnakeCaseNamingStrategy(),
			),
			new PropertySetterMethodExtractor(),
			new PropertyGetterMethodExtractor(),
			new HoistStrategyExtractor(
				reflector: new Reflector(),
			),
		];
	}

	/**
	 * @template T of class-string
	 * @return T
	 */
	public function subject(): string
	{
		return Subject::class;
	}

	public function expect(): ClassAnalyses
	{
		$properties = new PropertyAnalyses();
		$properties->add('id', new PropertyAnalysis(
			name: 'id',
			type: new BuiltinType(TypeIdentifier::STRING),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					PropertyDefaultValueExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', 'blah')->add('default_exists', true),
				)
				->add(
					SerializedPropertyNameExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', 'id'),
				)
				->add(
					PropertySetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::ConstructorArgument,
						constructorIndex: 0,
					)),
				)
				->add(
					PropertyGetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::GetterMethod,
						getterMethod: 'getId',
					)),
				)
				->add(
					HoistStrategyExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new HoistStrategy(
						enabled: false,
					)),
				),
		));
		$properties->add('myInt', new PropertyAnalysis(
			name: 'myInt',
			type: new BuiltinType(TypeIdentifier::INT),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					PropertyDefaultValueExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', 1234)->add('default_exists', true),
				)
				->add(
					SerializedPropertyNameExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', 'my_int'),
				)
				->add(
					PropertySetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					)),
				)
				->add(
					PropertyGetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					)),
				)
				->add(
					HoistStrategyExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new HoistStrategy(
						enabled: false,
					)),
				),
		));
		$properties->add('myString', new PropertyAnalysis(
			name: 'myString',
			type: new BuiltinType(TypeIdentifier::STRING),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					PropertyDefaultValueExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', 'meh')->add('default_exists', true),
				)
				->add(
					SerializedPropertyNameExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', 'my_string'),
				)
				->add(
					PropertySetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::SetterMethod,
						setterMethod: 'setMyString',
					)),
				)
				->add(
					PropertyGetterMethodExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::GetterMethod,
						getterMethod: 'getMyString',
					)),
				)
				->add(
					HoistStrategyExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', new HoistStrategy(
						enabled: false,
					)),
				),
		));
		$expect = new ClassAnalyses();
		$expect->add(Subject::class, new ClassAnalysis(
			name: Subject::class,
			properties: $properties,
		));

		return $expect;
	}
}
