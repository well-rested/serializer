<?php

declare(strict_types=1);

namespace Tests\Integration\Analysis\Extractors;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Analysis\Extractors\ClassAnalysisScenarios\NullableLinkFromChildToParent;
use Tests\Integration\Analysis\Extractors\ClassAnalysisScenarios\NullableRecursionWithSelf;
use Tests\Integration\Analysis\Extractors\ClassAnalysisScenarios\PromotedOptionalFields;
use Tests\Integration\Analysis\Extractors\ClassAnalysisScenarios\SettersAndGetters;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysisExtractor;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysisExtractor;
use WellRested\Serializer\Analysis\Reflector;

#[CoversClass(PropertyAnalysisExtractor::class)]
#[CoversClass(ClassAnalysisExtractor::class)]
class ClassAnalysisExtractorTest extends TestCase
{
	#[DataProvider('scenarios')]
	public function test_scenarios(string $scenario): void
	{
		$provider = new $scenario();
		$extractor = new ClassAnalysisExtractor(
			new PropertyAnalysisExtractor(
				reflector: new Reflector(),
				extensions: $provider->propertyExtractors(),
			),
		);
		$got = $extractor->extract($provider->subject());

		$this->assertEquals($provider->expect(), $got);
	}

	public static function scenarios(): Generator
	{
		yield 'setters_and_getters' => [SettersAndGetters::class];
		yield 'nullable_recursion_with_self' => [NullableRecursionWithSelf::class];
		yield 'nullable_link_from_child_to_parent' => [NullableLinkFromChildToParent::class];
		yield 'promoted_optional_fields' => [PromotedOptionalFields::class];
	}
}
