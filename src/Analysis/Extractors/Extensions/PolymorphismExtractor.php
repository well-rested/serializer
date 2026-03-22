<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors\Extensions;

use ReflectionProperty;
use RuntimeException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use WellRested\Serializer\Analysis\Reflector;
use WellRested\Serializer\Attributes\Polymorphic;
use WellRested\Serializer\Util\MixedDictionary;

class PolymorphismExtractor implements ExtendsPropertyExtraction
{
	public const EXTENSION_NAME = "builtin.polymorphism_extractor";

	public function __construct(
		protected Reflector $reflector,
	) {}

	protected function isValidUnion(Type $type): bool
	{
		if ($type instanceof UnionType) {
			foreach ($type->getTypes() as $subType) {
				// Can be an object, but can't be an interface as we can't construct one of those
				if (! $subType instanceof ObjectType) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	public function extract(ReflectionProperty $property): MixedDictionary
	{
		$type = $this->reflector->getPropertyType(
			$property->getDeclaringClass()->getName(),
			$property->getName(),
		);

		$isValidUnion = $this->isValidUnion($type);

		if (! $isValidUnion) {
			return $this->getDisabledConfig();
		}

		assert($type instanceof UnionType);
		return $this->getUnionConfig($property, $type);
	}

	/** @param UnionType<ObjectType<class-string>> $type */
	protected function getUnionConfig(ReflectionProperty $property, UnionType $type): MixedDictionary
	{
		$attr = $this->getPolymorphicAttribute($property);

		if ($attr === null) {
			return $this->getDisabledConfig();
		}

		if (count($type->getTypes()) !== count($attr->typeMap)) {
			throw new RuntimeException(
				'invalid polymorphic mapping for '
						. $property->getDeclaringClass()->getName()
						. '->' . $property->getName()
						. ': the type map must contain exactly one entry for each union case',
			);
		}

		foreach ($attr->typeMap as $option) {
			$reflClass = $this->reflector->reflectClass($option);

			if ($reflClass->isAbstract() || $reflClass->isInterface() || $reflClass->isTrait()) {
				throw new RuntimeException(
					'invalid polymorphic mapping for '
						. $property->getDeclaringClass()->getName()
						. '->' . $property->getName()
						. ': at least one case in the type map is an interface, trait, or abstract',
				);
			}

			if (! $this->unionContainsClass($type, $option)) {
				throw new RuntimeException(
					'invalid polymorphic mapping for '
						. $property->getDeclaringClass()->getName()
						. '->' . $property->getName()
						. ': type map contains mapping for class which is not in the union',
				);
			}
		}

		return (new MixedDictionary())->add('is_polymorphic', true)
			->add('field', $attr->field)
			->add('type_map', $attr->typeMap);
	}

	/**
	 * @param UnionType<ObjectType<class-string>> $type
	 * @param class-string $search
	 */
	protected function unionContainsClass(UnionType $type, string $search): bool
	{
		foreach ($type->getTypes() as $subType) {
			$className = $subType->getClassName();
			if ($className === $search) {
				return true;
			}
		}

		return false;
	}

	protected function getDisabledConfig(): MixedDictionary
	{
		return (new MixedDictionary())->add('is_polymorphic', false);
	}

	protected function getPolymorphicAttribute(ReflectionProperty $property): ?Polymorphic
	{
		$polymorphicAttr = $property->getAttributes(Polymorphic::class)[0] ?? null;

		return $polymorphicAttr !== null ? $polymorphicAttr->newInstance() : null;
	}

	public function extensionId(): string
	{
		return self::EXTENSION_NAME;
	}
}
