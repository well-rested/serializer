<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers\Traits;

use PhpOption\Option;
use RuntimeException;
use Symfony\Component\TypeInfo\Type;
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;

trait NormalizesRecursively
{
	protected ?NormalizerInterface $recursiveNormalizer;

	public function withNormalizer(NormalizerInterface $denormalizer): void
	{
		$this->recursiveNormalizer = $denormalizer;
	}

	/**
	 * @param Option<mixed> $data
	 */
	protected function recursivelyNormalize(Option $data, Type $type, string $path): mixed
	{
		if ($this->recursiveNormalizer === null) {
			throw new RuntimeException("recursive normalizer not set");
		}

		return $this->recursiveNormalizer->normalize($data, $type, $path);
	}
}
