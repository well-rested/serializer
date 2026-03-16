<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use RuntimeException;
use stdClass;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysis;
use WellRested\Serializer\Analysis\Extractors\ClassAnalysisExtractor;
use WellRested\Serializer\Analysis\Extractors\PropertyAnalysis;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Errors\FieldErrorType;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\Traits\DenormalizesRecursively;
use WellRested\Serializer\Normalizers\Traits\NormalizesRecursively;

class ObjectNormalizer implements DenormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface, NormalizerInterface
{
	use DenormalizesRecursively;
	use NormalizesRecursively;

	public function __construct(
		protected ClassAnalysisExtractor $extractor,
	) {}

	public function supportsDenormalization(Option $data, Type $type): bool
	{
		return $type instanceof ObjectType;
	}

	public function denormalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof ObjectType);

		if (! $data->isDefined()) {
			return new FieldError(
				location: $path,
				type: FieldErrorType::ValueIsRequired,
				value: None::create(),
			);
		}

		$value = $data->get();

		if (! is_array($value)) {
			return new FieldError(
				location: $path,
				type: FieldErrorType::ValueIsInvalidType,
				value: Some::create($value),
			);
		}

		/** @var class-string $className */
		$className = $type->getClassName();
		$analyses = $this->extractor->extract($className);
		$analysis = $analyses->get($className);

		if ($analysis === null) {
			throw new RuntimeException('analysis not found for: ' . $className);
		}

		$instance = $this->instantiate($analysis, $value, $path);

		if ($instance instanceof FieldErrors) {
			return $instance;
		}

		assert(is_object($instance));
		$errors = $this->setProperties($analysis, $instance, $value, $path);

		if (! $errors->isEmpty()) {
			return $errors;
		}

		return $instance;
	}

	/**
	 * @param array<mixed> $value
	 */
	protected function instantiate(ClassAnalysis $analysis, array $value, string $path): mixed
	{
		$errors = new FieldErrors();
		$args = [];
		$constructorArgsValid = true;

		foreach ($analysis->getProperties() as $property) {
			$strategy = $property->getSetterStrategy();

			if ($strategy->getMethod() !== SetPropertyStrategyMethod::ConstructorArgument) {
				continue;
			}

			$result = $this->resolvePropertyValue($value, $property, $path);

			if ($result instanceof FieldErrors) {
				$errors->merge($result);
				$constructorArgsValid = false;
				continue;
			}

			if ($result instanceof FieldError) {
				$errors->add($result);
				$constructorArgsValid = false;
				continue;
			}

			$args[$strategy->getConstructorIndex()] = $result;
		}

		if (!$constructorArgsValid) {
			return $errors;
		}

		$className = $analysis->getName();

		return new $className(...$args);
	}

	/**
	 * @param array<mixed> $value
	 */
	protected function setProperties(ClassAnalysis $analysis, object $instance, array $value, string $path): FieldErrors
	{
		$errors = new FieldErrors();

		foreach ($analysis->getProperties() as $property) {
			$strategy = $property->getSetterStrategy();

			if ($strategy->getMethod() !== SetPropertyStrategyMethod::PublicSetter
				&& $strategy->getMethod() !== SetPropertyStrategyMethod::SetterMethod) {
				continue;
			}

			$result = $this->resolvePropertyValue($value, $property, $path);

			if ($result instanceof FieldErrors) {
				$errors->merge($result);
				continue;
			}

			if ($result instanceof FieldError) {
				$errors->add($result);
				continue;
			}

			if ($strategy->getMethod() === SetPropertyStrategyMethod::PublicSetter) {
				$instance->{$property->getName()} = $result;
			} else {
				$instance->{$strategy->getSetterMethod()}($result);
			}
		}

		return $errors;
	}

	/**
	 * @param array<mixed> $data
	 * @return mixed|FieldError|FieldErrors
	 */
	protected function resolvePropertyValue(array $data, PropertyAnalysis $property, string $path): mixed
	{
		$serializedName = $property->getSerializedPropertyName();
		$propertyPath = $path !== '' ? $path . '.' . $serializedName : $serializedName;

		// dump($data);
		$wrappingStrategy = $property->getWrappingStrategy();

		if ($wrappingStrategy->isEnabled()) {
			$data = $data[$serializedName] ?? [];
			/** @var string $serializedName should not be possible for key to be null here */
			$serializedName = $wrappingStrategy->getKey();
			$propertyPath .= '.' . $serializedName;

			if (! is_array($data)) {
				return new FieldError(
					location: $propertyPath,
					type: FieldErrorType::ValueIsInvalidType,
					value: Some::create($data),
				);
			}
		}

		$isInData = array_key_exists($serializedName, $data);

		$propVal = match (true) {
			$isInData => Some::create($data[$serializedName]),
			$property->getDefaultValue()->isDefined() => Some::create($property->getDefaultValue()->get()),
			default => None::create(),
		};

		return $this->recursivelyDenormalize($propVal, $property->getType(), $propertyPath);
	}

	public function normalize(Option $data, Type $type, string $path): mixed
	{
		assert($type instanceof ObjectType);

		if (! $data->isDefined()) {
			return new stdClass();
		}

		/** @var object $container */
		$container = $data->get();
		/** @var class-string $className */
		$className = $type->getClassName();
		assert($container instanceof $className);

		$analyses = $this->extractor->extract($className);
		$analysis = $analyses->get($className);

		if ($analysis === null) {
			throw new RuntimeException('analysis not found for: ' . $className);
		}

		$normalized = [];

		foreach ($analysis->getProperties() as $property) {
			$getterStrategy = $property->getGetterStrategy();

			if ($getterStrategy->getMethod() === GetPropertyStrategyMethod::NotAvailable) {
				continue;
			}

			$propValue = match ($getterStrategy->getMethod()->value) {
				GetPropertyStrategyMethod::PublicGetter->value => $container->{$property->getName()},
				// Stan complains that the second case is always true, because right now there are no unsupported methods
				// but this is here to catch it in future if we do add any more.
				// @phpstan-ignore-next-line match.alwaysTrue
				GetPropertyStrategyMethod::GetterMethod->value => $container->{$getterStrategy->getGetterMethod()}(),
				default => throw new RuntimeException("unsupported getter strategy: " . $getterStrategy->getMethod()->value),
			};

			$normalizedPropValue = $this->recursivelyNormalize(
				data: Some::create($propValue),
				type: $property->getType(),
				path: $path . '.' . $property->getSerializedPropertyName(),
			);

			// We should never get an option, unless it's None
			if ($normalizedPropValue instanceof Option) {
				if ($normalizedPropValue->isDefined()) {
					throw new RuntimeException('should not receieve an option here, unless its none');
				}

				continue;
			}

			$wrappingStrategy = $property->getWrappingStrategy();

			if ($wrappingStrategy->isEnabled()) {
				$normalizedPropValue = [
					$wrappingStrategy->getKey() => $normalizedPropValue,
				];
			}

			$normalized[$property->getSerializedPropertyName()] = $normalizedPropValue;
		}

		if ($normalized === []) {
			// Return an stdClass so that later on in things like json_encode, this is treat as an empty object,
			// rather than an empty array.
			return new stdClass();
		}

		return $normalized;
	}

	public function supportsNormalization(Option $data, Type $type): bool
	{
		return $type instanceof ObjectType;
	}
}
