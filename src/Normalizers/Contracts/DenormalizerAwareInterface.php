<?php

declare(strict_types=1);

namespace WellRested\Serializer\Normalizers\Contracts;

interface DenormalizerAwareInterface
{
	public function withDenormalizer(DenormalizerInterface $denormalizer): void;
}
