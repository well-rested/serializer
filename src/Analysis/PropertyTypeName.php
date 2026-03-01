<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use PhpOption\Option;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;

enum PropertyTypeName: string
{
	case Int = 'int';
	case String = 'string';
	case Bool = 'bool';
	case Float = 'float';
	case Array = 'array';
	case Null = 'null';
	case Mixed = 'mixed';
	case Object = 'object';
	case Any = 'any';
	case Union = 'union';
	case Intersection = 'intersection';
	case Complex = 'class';
	case Interface = 'interface';
	case Trait = 'trait';
	case Option = 'option';

	public static function fromReflectionProperty(ReflectionProperty $prop): self
	{
		$type = $prop->getType();

		if ($type === null) {
			return self::Any;
		}
		if ($type instanceof ReflectionUnionType) {
			return self::Union;
		}

		if ($type instanceof ReflectionIntersectionType) {
			return self::Intersection;
		}

		if ($type instanceof ReflectionNamedType) {
			return match (true) {
				'self' === $type->getName() => self::Complex,
				Option::class === $type->getName() => self::Option,
				class_exists($type->getName(), true) => self::Complex,
				interface_exists($type->getName(), true) => self::Interface,
				trait_exists($type->getName(), true) => self::Trait,
				default => self::from($type->getName()),
			};
		}

		throw new RuntimeException('Unsupported ReflectionProperty type');
	}

	public function isScalar(): bool
	{
		return match ($this) {
			self::Int,
			self::String,
			self::Bool,
			self::Float,
			self::Null, => true,
			default => false,
		};
	}

	public function allowsNull(): bool
	{
		return match ($this) {
			self::Any,
			self::Mixed,
			self::Null, => true,
			default => false,
		};
	}
}
