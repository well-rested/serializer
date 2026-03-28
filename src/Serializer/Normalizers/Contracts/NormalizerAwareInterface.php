<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers\Contracts;

interface NormalizerAwareInterface
{
	public function withNormalizer(NormalizerInterface $denormalizer): void;
}
