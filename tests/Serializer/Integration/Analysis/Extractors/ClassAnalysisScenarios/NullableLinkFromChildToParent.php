<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\ClassAnalysisScenarios;

use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\TwoLevelRecursionChild;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\TwoLevelRecursionParent;
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

class NullableLinkFromChildToParent
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
		return TwoLevelRecursionParent::class;
	}

	public function expect(): ClassAnalyses
	{
		$expect = new ClassAnalyses();
		$expect->add(TwoLevelRecursionParent::class, new ClassAnalysis(
			name: TwoLevelRecursionParent::class,
			properties: (new PropertyAnalyses())->add('case3B', new PropertyAnalysis(
				name: 'case3B',
				type: new ObjectType(TwoLevelRecursionChild::class),
				extensions: (new PropertyAnalysisExtractionExtensions())
					->add(
						PropertyDefaultValueExtractor::EXTENSION_NAME,
						(new MixedDictionary())->add('value', null)->add('default_exists', false),
					)
					->add(
						SerializedPropertyNameExtractor::EXTENSION_NAME,
						(new MixedDictionary())->add('value', 'case3_b'),
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
			)),
		));

		$expect->add(TwoLevelRecursionChild::class, new ClassAnalysis(
			name: TwoLevelRecursionChild::class,
			properties: (new PropertyAnalyses())->add('case3A', new PropertyAnalysis(
				name: 'case3A',
				type: new NullableType(new ObjectType(TwoLevelRecursionParent::class)),
				extensions: (new PropertyAnalysisExtractionExtensions())
					->add(
						PropertyDefaultValueExtractor::EXTENSION_NAME,
						(new MixedDictionary())->add('value', null)->add('default_exists', false),
					)
					->add(
						SerializedPropertyNameExtractor::EXTENSION_NAME,
						(new MixedDictionary())->add('value', 'case3_a'),
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
			)),
		));

		return $expect;
	}
}
