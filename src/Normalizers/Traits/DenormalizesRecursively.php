<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers\Traits;

use PhpOption\Option;
use RuntimeException;
use Symfony\Component\TypeInfo\Type;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;

trait DenormalizesRecursively
{
	protected ?DenormalizerInterface $recursiveDenormalizer = null;

	public function withDenormalizer(DenormalizerInterface $denormalizer): void
	{
		$this->recursiveDenormalizer = $denormalizer;
	}

	/**
	 * @param Option<mixed> $data
	 */
	protected function recursivelyDenormalize(Option $data, Type $type, string $path): mixed
	{
		if ($this->recursiveDenormalizer === null) {
			throw new RuntimeException("recursive denormalizer not set");
		}

		return $this->recursiveDenormalizer->denormalize($data, $type, $path);
	}
}
