<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use RuntimeException;
use stdClass;
use WellRested\Serializer\Analysis\ClassAnalysis;
use WellRested\Serializer\Analysis\PropertyAnalysis;
use WellRested\Serializer\Analysis\TypeDefinitions\ArrayTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\BoolTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\ClassTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\CoercerInterface;
use WellRested\Serializer\Analysis\TypeDefinitions\FloatTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\IntegerTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\MixedTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\NullTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\ObjectTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\OptionTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\StringTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionAbstract;
use WellRested\Serializer\Analysis\TypeDefinitions\UnionTypeDefinition;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Exceptions\HoistTargetNotFoundException;
use WellRested\Serializer\Exceptions\IncorrectTypeForHoistException;

class Serializer implements SerializerInterface, DeserializerInterface
{
	protected FieldErrors $fieldErrors;

	protected string $rootType;

	public function __construct(
		protected ClassAnalyserInterface $analyser,
		protected CoercerInterface $coercer,
	) {
		$this->fieldErrors = new FieldErrors();
	}

	public function getRaisedErrors(): FieldErrors
	{
		return $this->fieldErrors;
	}

	/**
	 * Returns either an array or an stdClass. The stdClass is only really there
	 * to support the scenario where we have an empty object, but an empty array
	 * when converted to json would be just an array rather than '{}' which is
	 * what we'd actually want.
	 *
	 * @return array<string|int, mixed>|stdClass
	 */
	public function serialize(object $subject): array|stdClass
	{
		$this->rootType = get_class($subject);

		$value = $this->_serialize($subject);

		return $value;
	}

	/**
	 * @return array<string|int, mixed>|stdClass
	 */
	public function _serialize(object $subject, string $path = ''): array|stdClass
	{
		if ($subject instanceof stdClass) {
			$encoded = json_encode($subject);

			if ($encoded === false) {
				throw new RuntimeException('failed to encode subject');
			}

			/** @var array<string|int, mixed>|stdClass */
			$decoded = json_decode($encoded, true);
			return $decoded;
		}

		$subjectClass = get_class($subject);
		$analysis = $this->analyser->analyse($subjectClass)->get($subjectClass);

		if ($analysis === null) {
			throw new RuntimeException('class analysis missing');
		}

		$value = $this->serializeForClassAnalysis($analysis, $subject, $path);

		return $value->isDefined() ? $value->get() : new stdClass();
	}

	/** @return Option<array<string|int, mixed>|stdClass> */
	protected function serializeForClassAnalysis(ClassAnalysis $analysis, mixed $value, string $path): Option
	{
		if ($value instanceof Option && !$value->isDefined()) {
			return None::create();
		}

		if ($value instanceof Option) {
			$value = $value->get();
		}

		$data = [];

		foreach ($analysis->getProperties() as $property) {
			/** @var object $value */
			$serializedValue = $this->serializeProperty($property, $value, $path);

			if (!$serializedValue->isDefined()) {
				continue;
			}

			$data[$property->getSerializedName()] = $serializedValue->get();
		}

		if ($data === []) {
			$data = new stdClass();
		}

		/** @var Option<array<string|int, mixed>|stdClass> $option */
		$option = Some::create($data);

		return $option;
	}

	/** @return Option<mixed> */
	protected function serializeProperty(PropertyAnalysis $analysis, object $container, string $path): Option
	{
		$propertyPath = $this->concatPath($path, $analysis->getName());
		$propValue = $this->retrievePropertyValueFromContainer($analysis, $container);

		return match (true) {
			$analysis->shouldHoist() => $this->serializeHoistedProperty($analysis, $propValue, $propertyPath),
			default => $this->serializeValue($analysis->getTypeDefinition(), $propValue, $propertyPath),
		};
	}

	/** @return Option<mixed> */
	protected function serializeHoistedProperty(PropertyAnalysis $analysis, mixed $propValue, string $path): Option
	{
		$type = $analysis->getTypeDefinition();

		if (!$this->canHoistValueforProperty($analysis)) {
			throw new IncorrectTypeForHoistException($this->rootType, $path, $type->getName());
		}

		/**
		 * The canHoistValueforProperty check tells us this, but that may change
		 * with support for optional hoisted properties and poymorphism.
		 *
		 * @var ClassTypeDefinition $type
		 */
		$hoistTarget = $type->getName();

		$toHoist = $analysis->hoistProperty();
		$targetAnalysis = $this->analyser->analyse($hoistTarget)->get($hoistTarget);

		if ($targetAnalysis === null) {
			throw new RuntimeException('analysis not found');
		}

		if (!$targetAnalysis->getProperties()->has($toHoist)) {
			throw new HoistTargetNotFoundException($this->rootType, $path, $toHoist);
		}

		$hoistContainer = $this->serializeForClassAnalysis($targetAnalysis, $propValue, $this->concatPath($path, $targetAnalysis->getName()));

		if (!$hoistContainer->isDefined()) {
			return None::create();
		}

		$hoistContainerValue = $hoistContainer->get();

		if ($hoistContainerValue instanceof stdClass || ! array_key_exists($toHoist, $hoistContainerValue)) {
			// TODO: handle optional hoisted properties?
			// ignoring, because stan is a real pain in the arse with these options,
			// the actual typehints should be enough
			// @phpstan-ignore-next-line
			return Some::create(null);
		}

		return Some::create($hoistContainerValue[$toHoist]);
	}

	/**
	 * Later we'll probably support more here.
	 */
	protected function canHoistValueforProperty(PropertyAnalysis $analysis): bool
	{
		$type = $analysis->getTypeDefinition();

		return $type->is(ClassTypeDefinition::class);
	}

	/** @return Option<mixed> */
	protected function serializeValue(TypeDefinitionAbstract $type, mixed $value, string $path): Option
	{
		if ($type->is(OptionTypeDefinition::class)) {
			/** @var Option<mixed> $value */
			return !$value->isDefined() ? None::create() : $this->serializeValue(
				$type->getWrappedType(),
				$value->get(),
				$path,
			);
		} elseif ($type->is(ArrayTypeDefinition::class)) {
			/** @var array<string|int, mixed> $value */
			// @phpstan-ignore-next-line
			return Some::create(
				$this->serializeArrayValue($type, $value, $path),
			);
		} elseif ($type->is(ClassTypeDefinition::class)) {
			$analysis = $this->analyser->analyse($type->getName())->get($type->getName());

			if ($analysis === null) {
				throw new RuntimeException('analysis not found');
			}

			// @phpstan-ignore-next-line
			return $this->serializeForClassAnalysis(
				$analysis,
				$value,
				$path,
			);
		}
		return Some::create($value);
	}

	/**
	 * @param array<string|int, mixed> $values
	 *
	 * @return array<string|int, mixed>
	 */
	protected function serializeArrayValue(ArrayTypeDefinition $arrayType, array $values, string $path): array
	{
		$items = array_map(
			fn(mixed $item, string|int $index) => $this->serializeValue(
				$arrayType->getItemType(),
				$item,
				$this->concatPath($path, (string) $index),
			),
			$values,
			array_keys($values),
		);

		// shouldn't really be needed cause you can't really have an option in an array, it just wouldn't be there?
		return array_map(
			fn(Option $opt) => $opt->get(),
			array_filter($items, fn(Option $opt) => $opt->isDefined()),
		);
	}

	protected function retrievePropertyValueFromContainer(PropertyAnalysis $analysis, object $container): mixed
	{
		if ($analysis->shouldUseGetterMethod()) {
			return $container->{$analysis->getGetterMethod()}();
		}

		return $container->{$analysis->getName()};
	}

	/**
	 * @template T
	 *
	 * @param array<string|int, mixed> $data
	 * @param class-string<T> $target
	 *
	 * @return Option<T>
	 */
	public function deserialize(array $data, string $target): Option
	{
		$this->rootType = $target;
		$this->fieldErrors = new FieldErrors();

		$value = $this->_deserialize($data, $target);

		if (!$this->fieldErrors->isEmpty()) {
			return None::create();
		}

		return $value;
	}

	/**
	 * @template T
	 *
	 * @param array<string|int, mixed> $data
	 * @param class-string<T> $target
	 *
	 * @return Option<T>
	 */
	protected function _deserialize(array $data, string $target, string $path = ''): Option
	{
		$analyses = $this->analyser->analyse($target);
		$analysis = $analyses->get($target);

		if ($analysis === null) {
			throw new RuntimeException('analysis not found');
		}

		$targetClass = $analysis->getName();

		$args = $this->gatherConstructorArgs($data, $analysis, $path);

		if (!$args->isDefined()) {
			return None::create();
		}

		/** @var array<string|int, Option<mixed>> $argsArray */
		$argsArray = $args->get();

		$argsValue = array_map(fn(Option $opt) => $opt->get(), $argsArray);
		$instance = new $targetClass(...$argsValue);

		$this->setNonConstructorProperties($instance, $data, $analysis, $path);

		// @phpstan-ignore-next-line
		return Some::create($instance);
	}

	protected function concatPath(string $path, string $part): string
	{
		return '' == $path ? $part : "$path.$part";
	}

	/**
	 * @param array<string|int, mixed> $data
	 */
	protected function setNonConstructorProperties(object $instance, array $data, ClassAnalysis $analysis, string $path): void
	{
		foreach ($analysis->getProperties() as $property) {
			if ($property->shouldPassThroughConstructor()) {
				continue;
			}

			$value = $this->buildPropertyValue(
				$property,
				$data,
				$this->concatPath($path, $property->getSerializedName()),
			);

			if (!$value->isDefined() && !$property->isOptional()) {
				// if it's a None, then we couldn't determine a value, and there was an error
				continue;
			}

			if ($property->shouldUsePublicSetter()) {
				$instance->{$property->getName()} = $property->isOptional() ? $value : $value->get();
				continue;
			}

			if ($property->shouldUseSetterMethod()) {
				$instance->{$property->getSetterMethod()}(
					$property->isOptional() ? $value : $value->get()
				);
			}
		}
	}

	/**
	 * @param array<string|int, mixed> $data
	 *
	 * @return Option<array<string|int, mixed>>
	 */
	protected function gatherConstructorArgs(array $data, ClassAnalysis $analysis, string $path): Option
	{
		$args = [];

		$argMissing = false;

		foreach ($analysis->getProperties() as $property) {
			if (!$property->shouldPassThroughConstructor()) {
				continue;
			}

			$value = $this->buildPropertyValue(
				$property,
				$data,
				$this->concatPath($path, $property->getSerializedName()),
			);

			if (!$value->isDefined() && !$property->isOptional()) {
				$argMissing = true;
				continue;
			}

			// If it's optional, we wrap it in another some, so that when it's
			// unwrapped later we pass an option
			$args[$property->getConstructorIndex()] = $property->isOptional() ? new Some($value) : $value;
		}

		if ($argMissing) {
			return None::create();
		}

		// @phpstan-ignore-next-line
		return new Some($args);
	}

	/**
	 * @param array<string|int, mixed> $data
	 *
	 * @return Option<mixed>
	 */
	protected function buildPropertyValue(PropertyAnalysis $property, array $data, string $path): Option
	{
		$serializedName = $property->getSerializedName();
		$valueIsPresent = array_key_exists($serializedName, $data);
		$type = $property->getTypeDefinition();

		if ($property->isOptional() && !$valueIsPresent) {
			return None::create();
		}

		$value = $data[$serializedName] ?? null;

		if (!$valueIsPresent) {
			if ($property->hasDefault()) {
				return new Some($property->getDefault());
			}

			$this->fieldErrors->add(new FieldError(
				location: $path,
				message: 'value is required',
				value: None::create(),
			));

			return None::create();
		}

		if (null === $value) {
			if ($property->allowsNull()) {
				// @phpstan-ignore-next-line
				return Some::create(null);
			}

			$this->recordInvalidTypeError($path, $property->getTypeDefinition(), $value);

			return None::create();
		}

		return $this->convertPropertyValueToCompatibleType($type, $value, $path);
	}

	protected function recordInvalidTypeError(string $path, TypeDefinitionAbstract $type, mixed $value): void
	{
		$this->fieldErrors->add(new FieldError(
			location: $path,
			message: 'invalid type, expected: ' . $type->getName(),
			value: new Some($value),
		));
	}

	/**
	 * @return Option<mixed>
	 */
	protected function convertPropertyValueToCompatibleType(TypeDefinitionAbstract $type, mixed $value, string $path): Option
	{
		$isCompatible = $this->valueIsCompatibleWithType($type, $value);

		if ($isCompatible) {
			return match (true) {
				$type->is(OptionTypeDefinition::class) => $this->convertValueForOptionType($type, $value, $path),
				$type->is(UnionTypeDefinition::class) => $this->convertValueForUnionType($type, $value, $path),
				// @phpstan-ignore-next-line the value is an array
				$type->is(ArrayTypeDefinition::class) => $this->convertValueForArrayType($type, $value, $path),
				// @phpstan-ignore-next-line
				$type->is(ClassTypeDefinition::class) => $this->_deserialize($value, $type->getName(), $path),
				$type->is(ObjectTypeDefinition::class) => Some::create((object) $value),
				default => Some::create($value),
			};
		}

		if ($this->coercer->canCoerce($type, $value)) {
			return new Some($this->coercer->coerce($type, $value));
		}

		$this->recordInvalidTypeError($path, $type, $value);

		return None::create();
	}

	/** @return Option<mixed> */
	protected function convertValueForOptionType(OptionTypeDefinition $type, mixed $value, string $path): Option
	{
		$newValue = $this->convertPropertyValueToCompatibleType($type->getWrappedType(), $value, $path);

		return $newValue;
	}

	/** @return Option<mixed> */
	protected function convertValueForUnionType(UnionTypeDefinition $unionType, mixed $value, string $path): Option
	{
		$concrete = $this->getConcreteForUnion($unionType, $value);

		if (null === $concrete) {
			return None::create();
		}

		return $this->convertPropertyValueToCompatibleType($concrete, $value, $path);
	}

	/**
	 * @param array<string|int, mixed> $values
	 *
	 * @return Option<mixed>
	 */
	protected function convertValueForArrayType(ArrayTypeDefinition $arrayType, array $values, string $path): Option
	{
		$itemType = $arrayType->getItemType();

		$results = [];

		foreach ($values as $index => $value) {
			$newValue = $this->convertPropertyValueToCompatibleType($itemType, $value, $this->concatPath($path, (string) $index));

			if (!$newValue->isDefined()) {
				return None::create();
			}

			$results[] = $newValue->get();
		}

		// @phpstan-ignore-next-line
		return Some::create($results);
	}

	protected function valueIsCompatibleWithType(TypeDefinitionAbstract $type, mixed $value): bool
	{
		return match (true) {
			$type->is(UnionTypeDefinition::class) => null !== $this->getConcreteForUnion($type, $value),
			$type->is(IntegerTypeDefinition::class) => is_int($value),
			$type->is(StringTypeDefinition::class) => is_string($value),
			$type->is(BoolTypeDefinition::class) => is_bool($value),
			$type->is(FloatTypeDefinition::class) => is_float($value),
			$type->is(ArrayTypeDefinition::class) => is_array($value) && array_is_list($value),
			$type->is(NullTypeDefinition::class) => is_null($value),
			$type->is(MixedTypeDefinition::class) => true,
			$type->is(ObjectTypeDefinition::class),
			$type->is(ClassTypeDefinition::class) => is_array($value),
			// TODO: help static analysis out here, doesn't know that getWrappedType does in fact exist...
			$type->is(OptionTypeDefinition::class) => $this->valueIsCompatibleWithType($type->getWrappedType(), $value),

			default => false,
		};
	}

	protected function getConcreteForUnion(UnionTypeDefinition $unionType, mixed $value): ?TypeDefinitionAbstract
	{
		foreach ($unionType->getPossibleTypes() as $type) {
			if ($this->valueIsCompatibleWithType($type, $value)) {
				return $type;
			}
		}

		return null;
	}
}
