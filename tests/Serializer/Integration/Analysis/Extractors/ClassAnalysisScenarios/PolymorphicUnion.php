<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\ClassAnalysisScenarios;

use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\Polymorphic\Union\Container;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\Polymorphic\Union\TypeA;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\Polymorphic\Union\TypeB;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\Polymorphic\Union\TypeC;
use WellRested\Serializer\Analysis\Extractors\ClassAnalyses;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysis;
use WellRested\Serializer\Analysis\Extractors\Extensions\ExtendsPropertyExtraction;
use WellRested\Serializer\Analysis\Extractors\Extensions\HoistStrategyExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PolymorphismExtractor;
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
use WellRested\Serializer\Analysis\PolymorphismStrategy;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Naming\SnakeCaseNamingStrategy;
use WellRested\Serializer\Util\MixedDictionary;

class PolymorphicUnion
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
			new PolymorphismExtractor(
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
		return Container::class;
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
					(new MixedDictionary())->add('value', new PolymorphismStrategy(
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
					(new MixedDictionary())->add('value', new PolymorphismStrategy(
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
					(new MixedDictionary())->add('value', new PolymorphismStrategy(
						enabled: false,
					)),
				),
		));
		$expect = new ClassAnalyses();
		$expect->add(
			Container::class,
			new ClassAnalysis(
				name: Container::class,
				properties: (new PropertyAnalyses())
				->add(
					'someProperty',
					new PropertyAnalysis(
						name: 'someProperty',
						type: new UnionType(
							new ObjectType(TypeA::class),
							new ObjectType(TypeB::class),
							new ObjectType(TypeC::class),
						),
						extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'some_property'),
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
						)
						->add(
							PolymorphismExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new PolymorphismStrategy(
								enabled: true,
								field: '@type',
								typeMap: [
									'a' => TypeA::class,
									'b' => TypeB::class,
									'c' => TypeC::class,
								],
							)),
						),
					),
				),
			),
		);
		$expect->add(
			TypeA::class,
			new ClassAnalysis(
				name: TypeA::class,
				properties: (new PropertyAnalyses())
				->add('propertyA', new PropertyAnalysis(
					name: 'propertyA',
					type: new BuiltinType(TypeIdentifier::INT),
					extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'property_a'),
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
						)
						->add(
							PolymorphismExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new PolymorphismStrategy(
								enabled: false,
							)),
						),
				)),
			),
		);

		$expect->add(
			TypeB::class,
			new ClassAnalysis(
				name: TypeB::class,
				properties: (new PropertyAnalyses())
				->add('propertyB', new PropertyAnalysis(
					name: 'propertyB',
					type: new BuiltinType(TypeIdentifier::INT),
					extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'property_b'),
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
						)
						->add(
							PolymorphismExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new PolymorphismStrategy(
								enabled: false,
							)),
						),
				)),
			),
		);

		$expect->add(
			TypeC::class,
			new ClassAnalysis(
				name: TypeC::class,
				properties: (new PropertyAnalyses())
				->add('propertyC', new PropertyAnalysis(
					name: 'propertyC',
					type: new BuiltinType(TypeIdentifier::INT),
					extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'property_c'),
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
						)
						->add(
							PolymorphismExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new PolymorphismStrategy(
								enabled: false,
							)),
						),
				)),
			),
		);

		return $expect;
	}
}
