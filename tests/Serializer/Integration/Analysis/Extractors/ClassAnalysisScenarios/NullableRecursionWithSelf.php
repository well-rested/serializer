<?php

declare(strict_types=1);

namespace Tests\Serializer\Integration\Analysis\Extractors\ClassAnalysisScenarios;

use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Tests\Serializer\Integration\Analysis\Extractors\Fixture\RecursesWithSelfNullably;
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

class NullableRecursionWithSelf
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
		return RecursesWithSelfNullably::class;
	}

	public function expect(): ClassAnalyses
	{
		$properties = new PropertyAnalyses();
		$properties->add('anotherMe', new PropertyAnalysis(
			name: 'anotherMe',
			type: new NullableType(new ObjectType(RecursesWithSelfNullably::class)),
			extensions: (new PropertyAnalysisExtractionExtensions())
				->add(
					PropertyDefaultValueExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', null)->add('default_exists', false),
				)
				->add(
					SerializedPropertyNameExtractor::EXTENSION_NAME,
					(new MixedDictionary())->add('value', 'another_me'),
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
		$expect = new ClassAnalyses();
		$expect->add(RecursesWithSelfNullably::class, new ClassAnalysis(
			name: RecursesWithSelfNullably::class,
			properties: $properties,
		));

		return $expect;
	}
}
