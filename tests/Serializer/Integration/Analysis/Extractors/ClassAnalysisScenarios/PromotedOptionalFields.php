<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\ClassAnalysisScenarios;

use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\PromotedOptionalFieldsA;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\PromotedOptionalFieldsB;
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
use WellRested\Serializer\Analysis\Types\OptionType;
use WellRested\Serializer\Util\MixedDictionary;
use WellRested\Serializer\Naming\SnakeCaseNamingStrategy;

class PromotedOptionalFields
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
		return PromotedOptionalFieldsA::class;
	}

	public function expect(): ClassAnalyses
	{
		$expect = new ClassAnalyses();
		$expect->add(PromotedOptionalFieldsA::class, new ClassAnalysis(
			name: PromotedOptionalFieldsA::class,
			properties: (new PropertyAnalyses())
				->add(
					'email',
					new PropertyAnalysis(
						name: 'email',
						type: new OptionType(new BuiltinType(TypeIdentifier::STRING)),
						extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'email'),
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
								method: GetPropertyStrategyMethod::PublicGetter,
							)),
						)
						->add(
							HoistStrategyExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new HoistStrategy(
								enabled: false,
							)),
						),
					),
				)->add(
					'firstName',
					new PropertyAnalysis(
						name: 'firstName',
						type: new OptionType(new BuiltinType(TypeIdentifier::STRING)),
						extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'first_name'),
						)
						->add(
							PropertySetterMethodExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new SetPropertyStrategy(
								method: SetPropertyStrategyMethod::ConstructorArgument,
								constructorIndex: 1,
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
					),
				)->add(
					'lastName',
					new PropertyAnalysis(
						name: 'lastName',
						type: new OptionType(new BuiltinType(TypeIdentifier::STRING)),
						extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'last_name'),
						)
						->add(
							PropertySetterMethodExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new SetPropertyStrategy(
								method: SetPropertyStrategyMethod::ConstructorArgument,
								constructorIndex: 2,
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
					),
				)->add(
					'sub',
					new PropertyAnalysis(
						name: 'sub',
						type: new OptionType(new ObjectType(PromotedOptionalFieldsB::class)),
						extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'sub'),
						)
						->add(
							PropertySetterMethodExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', new SetPropertyStrategy(
								method: SetPropertyStrategyMethod::ConstructorArgument,
								constructorIndex: 3,
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
					),
				),
		));

		$expect->add(PromotedOptionalFieldsB::class, new ClassAnalysis(
			name: PromotedOptionalFieldsB::class,
			properties: (new PropertyAnalyses())
				->add(
					'line1',
					new PropertyAnalysis(
						name: 'line1',
						type: new OptionType(new BuiltinType(TypeIdentifier::STRING)),
						extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'line1'),
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
					),
				)->add(
					'line2',
					new PropertyAnalysis(
						name: 'line2',
						type: new OptionType(new BuiltinType(TypeIdentifier::STRING)),
						extensions: (new PropertyAnalysisExtractionExtensions())
						->add(
							PropertyDefaultValueExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', null)->add('default_exists', false),
						)
						->add(
							SerializedPropertyNameExtractor::EXTENSION_NAME,
							(new MixedDictionary())->add('value', 'line2'),
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
					),
				),
		));

		return $expect;
	}
}
