<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use PhpOption\Option;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Analysis\Types\OptionType;

class Reflector
{
	protected PropertyInfoExtractor $propertyInfoExtractor;

	/** @var array<int, callable(Type): ?Type> */
	protected array $typeOverriders;

	public function __construct()
	{
		$this->propertyInfoExtractor = new PropertyInfoExtractor(
			typeExtractors: [
				new PhpDocExtractor(),
				new ReflectionExtractor(),
			],
		);

		$this->typeOverriders = [
			[$this, 'overrideOptionType'],
		];
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @return ReflectionClass<T>
	 */
	public function reflectClass(string $class): ReflectionClass
	{
		return new ReflectionClass($class);
	}

	protected function overrideType(Type $type): ?Type
	{
		foreach ($this->typeOverriders as $func) {
			$got = $func($type);

			if ($got !== null) {
				return $got;
			}
		}

		return null;
	}

	/**
	 * Soooo...when overriding the generic option type a var docblock like
	 * Option<SomeClass>, then because it is an iterable, symfony property info
	 * treats it like a collection. This detects that scenario and then overrides
	 * with what the custom OptionType that makes this easier to work with.
	 */
	protected function overrideOptionType(Type $type): ?Type
	{
		if (! $type instanceof CollectionType) {
			return null;
		}

		$wrapped = $type->getWrappedType();

		if (! $wrapped instanceof GenericType) {
			return null;
		}

		$genericWrapped = $wrapped->getWrappedType();

		if (! $genericWrapped instanceof ObjectType) {
			return null;
		}

		if ($genericWrapped->getClassName() !== Option::class) {
			return null;
		}

		return new OptionType(
			$type->getCollectionValueType(),
		);
	}

	/**
	 * @param class-string $class
	 */
	public function getPropertyType(string $class, string $property): Type
	{
		$type = $this->propertyInfoExtractor->getType($class, $property);

		if ($type === null) {
			return new BuiltinType(TypeIdentifier::MIXED);
		}

		if (($overridden = $this->overrideOptionType($type)) !== null) {
			return $overridden;
		}

		return $type;
	}
}
