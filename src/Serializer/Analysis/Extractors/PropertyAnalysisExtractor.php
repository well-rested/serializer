<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors;

use ReflectionProperty;
use WellRested\Serializer\Analysis\Extractors\Extensions\ExtendsPropertyExtraction;
use WellRested\Serializer\Analysis\Reflector;

class PropertyAnalysisExtractor
{
	public function __construct(
		protected Reflector $reflector,
		/**
		 * @var ExtendsPropertyExtraction[] $extensions
		 */
		protected array $extensions,
	) {}

	/**
	 * @param class-string $classString
	 */
	public function extract(string $classString): PropertyAnalyses
	{
		$refl = $this->reflector->reflectClass($classString);

		$properties = new PropertyAnalyses();

		foreach ($refl->getProperties() as $property) {
			$analysis = $this->analyseProperty($property);

			$properties->add(
				$analysis->getName(),
				$analysis,
			);
		}

		return $properties;
	}

	protected function analyseProperty(ReflectionProperty $property): PropertyAnalysis
	{
		$type = $this->reflector->getPropertyType(
			$property->getDeclaringClass()->getName(),
			$property->getName(),
		);

		$extensionResults = new PropertyAnalysisExtractionExtensions();

		foreach ($this->extensions as $extension) {
			$extensionResults->add($extension->extensionId(), $extension->extract($property));
		}

		return new PropertyAnalysis(
			name: $property->getName(),
			type: $type,
			extensions: $extensionResults,
		);
	}
}
