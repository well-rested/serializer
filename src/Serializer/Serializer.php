<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use RuntimeException;
use stdClass;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use WellRested\Serializer\Errors\FieldError;
use WellRested\Serializer\Errors\FieldErrors;
use WellRested\Serializer\Exceptions\DeserializationException;
use WellRested\Serializer\Exceptions\SerializationException;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerAwareInterface;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;

class Serializer implements DenormalizerInterface, NormalizerInterface
{
	protected FieldErrors $fieldErrors;

	/**
	 * @param array<DenormalizerInterface|NormalizerInterface> $normalizers Normalizers are tried in
	 *   order; the first whose supportsDenormalization() returns true handles the
	 *   value. If none match, an exception is thrown.
	 */
	public function __construct(
		protected array $normalizers = [],
	) {
		foreach ($this->normalizers as $normalizer) {
			if ($normalizer instanceof DenormalizerInterface && $normalizer instanceof DenormalizerAwareInterface) {
				$normalizer->withDenormalizer($this);
			}

			if ($normalizer instanceof NormalizerInterface && $normalizer instanceof NormalizerAwareInterface) {
				$normalizer->withNormalizer($this);
			}
		}

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
		$this->fieldErrors = new FieldErrors();

		$value = $this->_serialize($subject);

		if (! $this->fieldErrors->isEmpty() || ! $value->isDefined()) {
			throw new SerializationException(
				subject: $subject,
				errors: $this->fieldErrors,
			);
		}

		return $value->get();
	}

	/**
	 * @return Option<array<mixed>|stdClass>
	 */
	public function _serialize(object $subject, string $path = ''): Option
	{
		if ($subject instanceof stdClass) {
			$encoded = json_encode($subject);

			if ($encoded === false) {
				throw new RuntimeException('failed to encode subject');
			}

			/** @var array<string|int, mixed>|stdClass */
			$decoded = json_decode($encoded, true);

			return Some::create($decoded);
		}

		$className = get_class($subject);
		$result = $this->normalize(
			// Option<mixed> is more permissive that Option<object> and this is fine
			// @phpstan-ignore-next-line
			Some::create($subject),
			new ObjectType($className),
			$path,
		);

		if ($result instanceof FieldErrors) {
			$this->fieldErrors->merge($result);
			return None::create();
		}

		if ($result instanceof FieldError) {
			$this->fieldErrors->add($result);
			return None::create();
		}

		if (! is_array($result) && ! $result instanceof stdClass) {
			return None::create();
		}

		return Some::create($result);
	}

	public function supportsNormalization(Option $data, Type $type): bool
	{
		return true;
	}

	public function normalize(Option $data, Type $type, string $path): mixed
	{
		$normalizer = $this->getNormalizer($data, $type);

		if ($normalizer !== null) {
			return $normalizer->normalize($data, $type, $path);
		}

		throw new RuntimeException('no normalizer found for value type: ' . (string) $type);
	}

	/**
	 *
	 * @param Option<mixed> $data
	 */
	protected function getNormalizer(Option $data, Type $type): ?NormalizerInterface
	{
		foreach ($this->normalizers as $normalizer) {
			if (! $normalizer instanceof NormalizerInterface) {
				continue;
			}

			if ($normalizer->supportsNormalization($data, $type)) {
				return $normalizer;
			}
		}

		return null;
	}

	/**
	 * @template T
	 *
	 * @param array<string|int, mixed> $data
	 * @param class-string<T> $target
	 *
	 * @return T
	 */
	public function deserialize(array $data, string $target): mixed
	{
		$this->fieldErrors = new FieldErrors();

		$value = $this->_deserialize($data, $target);

		if (! $this->fieldErrors->isEmpty() || ! $value->isDefined()) {
			throw new DeserializationException(
				targetClass: $target,
				data: $data,
				errors: $this->fieldErrors,
			);
		}

		return $value->get();
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
		/** @var Option<mixed> $dataOption */
		$dataOption = Some::create($data);
		$result = $this->denormalize($dataOption, new ObjectType($target), $path);

		if ($result instanceof FieldErrors) {
			$this->fieldErrors->merge($result);
			return None::create();
		}

		if ($result instanceof FieldError) {
			$this->fieldErrors->add($result);
			return None::create();
		}

		// @phpstan-ignore-next-line
		return Some::create($result);
	}

	/**
	 * Runs the value through the normalizer chain, falling back to built-in
	 * scalar/option/nullable handling. Returns the denormalized value, a
	 * FieldError (single field problem), or FieldErrors (nested object problems).
	 *
	 * @inheritdoc
	 */
	public function denormalize(Option $data, Type $type, string $path): mixed
	{
		$denormalizer = $this->getDenormalizer($data, $type);

		if ($denormalizer !== null) {
			return $denormalizer->denormalize($data, $type, $path);
		}

		throw new RuntimeException('no normalizer found for value type: ' . (string) $type);
	}

	/**
	 *
	 * @param Option<mixed> $data
	 */
	protected function getDenormalizer(Option $data, Type $type): ?DenormalizerInterface
	{
		foreach ($this->normalizers as $normalizer) {
			if (! $normalizer instanceof DenormalizerInterface) {
				continue;
			}

			if ($normalizer->supportsDenormalization($data, $type)) {
				return $normalizer;
			}
		}

		return null;
	}

	/**
	 * This should be able to denormalize anything.
	 *
	 * @inheritdoc
	 */
	public function supportsDenormalization(Option $data, Type $type): bool
	{
		return true;
	}
}
