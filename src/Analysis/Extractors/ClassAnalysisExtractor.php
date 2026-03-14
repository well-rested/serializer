<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors;

use InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Analysis\Types\OptionType;

class ClassAnalysisExtractor
{
	public function __construct(
		protected PropertyAnalysisExtractor $propertyAnalysisExtractor,
		// protected Reflector $reflector,
	) {}

	/**
	 * @param class-string $class
	 */
	public function extract(string $class): ClassAnalyses
	{
		return $this->doExtract($class, new ClassAnalyses());
	}

	/**
	 * @param class-string $class
	 */
	protected function doExtract(string $class, ClassAnalyses $analyses): ClassAnalyses
	{
		if (!class_exists($class, true)) {
			throw new InvalidArgumentException("value is not a class: $class");
		}

		$properties = $this->propertyAnalysisExtractor->extract($class);

		$analysis = new ClassAnalysis(
			name: $class,
			properties: $properties,
		);

		$analyses->add($class, $analysis);

		$this->handleClassDependencies($analyses, $analysis);

		return $analyses;
	}

	protected function handleClassDependencies(ClassAnalyses $analyses, ClassAnalysis $analysis): void
	{
		foreach ($analysis->getProperties() as $property) {
			$type = $property->getType();

			$this->extractFromType($analyses, $type);
		}
	}

	protected function extractFromType(ClassAnalyses $analyses, Type $type): void
	{
		match (true) {
			$type instanceof ObjectType => $this->extractFromObjectType($analyses, $type),
			$type instanceof NullableType => $this->extractFromNullableType($analyses, $type),
			$type instanceof UnionType => $this->extractFromUnionType($analyses, $type),
			$type instanceof OptionType => $this->extractFromOptionType($analyses, $type),
			default => new ClassAnalyses(),
		};
	}

	/**
	 * @param ObjectType<class-string> $type
	 */
	protected function extractFromObjectType(ClassAnalyses $analyses, ObjectType $type): void
	{
		/** @var class-string $classToExtract */
		$classToExtract = $type->getClassName();

		if ($analyses->has($classToExtract)) {
			return;
		}

		$this->doExtract($classToExtract, $analyses);
	}

	/**
	 * @param UnionType<Type> $type
	 */
	protected function extractFromUnionType(ClassAnalyses $analyses, UnionType $type): void
	{
		foreach ($type->getTypes() as $subType) {
			$this->extractFromType($analyses, $subType);
		}
	}

	protected function extractFromOptionType(ClassAnalyses $analyses, OptionType $type): void
	{
		$this->extractFromType($analyses, $type->getWrappedType());
	}

	/**
	 * @param NullableType<Type> $type
	 */
	protected function extractFromNullableType(ClassAnalyses $analyses, NullableType $type): void
	{
		$this->extractFromType($analyses, $type->getWrappedType());
	}
}
