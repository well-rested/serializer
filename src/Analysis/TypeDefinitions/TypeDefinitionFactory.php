<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\TypeDefinitions;

use InvalidArgumentException;
use WellRested\Serializer\Attributes\Field;
use PhpOption\Option;
use ReflectionNamedType;
use ReflectionProperty;

class TypeDefinitionFactory implements TypeDefinitionFactoryInterface
{
	public function fromReflectionProperty(ReflectionProperty $property): TypeDefinitionAbstract
	{
		$fieldAttrs = $property->getAttributes(Field::class);
		$fieldAttr = ($fieldAttrs[0] ?? null) !== null ? $fieldAttrs[0]->newInstance() : null;

		$type = $property->getType();

		if (null === $type) {
			if (null !== $fieldAttr?->type) {
				return $this->fromString($fieldAttr->type);
			}

			return new MixedTypeDefinition();
		}

		if ($type instanceof ReflectionNamedType && Option::class === $type->getName()) {
			if (null === $fieldAttr || null === $fieldAttr->type) {
				throw new InvalidArgumentException('Option types must have a field property with a type attribute: ' . $property->getDeclaringClass()->getName() . '->' . $property->getName());
			}

			return new OptionTypeDefinition(
				type: $this->fromString($fieldAttr->type),
			);
		}

		if (null !== $fieldAttr?->type) {
			return $this->fromString($fieldAttr->type);
		}

		return $this->fromString((string) $type);
	}

	// TODO: decide how to deal with a reference to self...bit tricky cause we
	// lack context of where this was defined...we could just not support it and
	// expect the class to typehint on itself; not a horrible way tbh.
	public function fromString(string $type): TypeDefinitionAbstract
	{
		// dump("fromString: $type");
		$type = $this->trimParenthesesIfRequired($type);

		$allowsNull = str_starts_with($type, '?');

		$type = trim($type, '?');

		$typeDefinition = match (true) {
			$this->isUnion($type) => $this->unionFromString($type),
			$this->isTypedArray($type) => $this->typedArrayFromString($type),
			$this->isIntersection($type) => $this->intersectionFromString($type),
			class_exists($type, true) => new ClassTypeDefinition(fqdn: $type),
			interface_exists($type, true) => new InterfaceTypeDefinition(fqdn: $type),
			trait_exists($type, true) => new TraitTypeDefinition(fqdn: $type),
			'int' == $type => new IntegerTypeDefinition(),
			'bool' == $type => new BoolTypeDefinition(),
			'float' == $type => new FloatTypeDefinition(),
			'null' == $type => new NullTypeDefinition(),
			'object' == $type => new ObjectTypeDefinition(),
			'string' == $type => new StringTypeDefinition(),
			'array' == $type => new ArrayTypeDefinition(new MixedTypeDefinition()),
			'mixed' == $type => new MixedTypeDefinition(),
		};

		if (!$allowsNull) {
			return $typeDefinition;
		}

		return new UnionTypeDefinition(
			$typeDefinition,
			new NullTypeDefinition(),
		);
	}

	protected function isTypedArray(string $type): bool
	{
		return str_ends_with($type, '[]');
	}

	protected function typedArrayFromString(string $type): TypeDefinitionAbstract
	{
		$itemType = substr($type, 0, -2);

		return new ArrayTypeDefinition(
			$this->fromString($itemType),
		);
	}

	protected function isIntersection(string $type): bool
	{
		return str_contains($type, '&');
	}

	protected function isUnion(string $type): bool
	{
		return count($this->splitTypeString($type)) > 1;
	}

	protected function intersectionFromString(string $intersectionType): TypeDefinitionAbstract
	{
		$types = [];
		$typeNames = explode('&', $intersectionType);

		foreach ($typeNames as $typeName) {
			$types[] = $this->fromString($typeName);
		}

		return new IntersectionTypeDefinition(
			...$types,
		);
	}

	protected function unionFromString(string $unionType): TypeDefinitionAbstract
	{
		$types = [];
		$typeNames = $this->splitTypeString($unionType);

		foreach ($typeNames as $typeName) {
			$types[] = $this->fromString($typeName);
		}

		return new UnionTypeDefinition(
			...$types,
		);
	}

	protected function trimParenthesesIfRequired(string $type): string
	{
		if (str_starts_with($type, '(') && str_ends_with($type, ')')) {
			return substr($type, 1, -1);
		}

		return $type;
	}

	/**
	 * Honestly ChatGPT'd this, made life alot easier. Tests should cover this to
	 * be safe. Looks pretty grim, and there's prpbably a smarter way.
	 *
	 * One to look at on a rainy day.
	 */
	protected function splitTypeString(string $typeString): array
	{
		$result = [];
		$buffer = '';
		$depth = 0;

		$length = strlen($typeString);
		for ($i = 0; $i < $length; ++$i) {
			$char = $typeString[$i];

			if ('(' === $char) {
				++$depth;
			} elseif (')' === $char) {
				--$depth;
			}

			// Check if we are at a top-level | to split
			if ('|' === $char && 0 === $depth) {
				$result[] = trim($buffer);
				$buffer = '';
			} else {
				$buffer .= $char;
			}
		}

		// Add the last piece
		if ('' !== $buffer) {
			$result[] = trim($buffer);
		}

		return $result;
	}
}
