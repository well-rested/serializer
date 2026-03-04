<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use WellRested\Serializer\Errors\FieldErrors;
use PhpOption\Option;

interface DeserializerInterface
{
	/**
	 * @template T
	 *
	 * @param array<int|string, mixed> $data
	 * @param class-string<T> $target
	 *
	 * @return Option<T>
	 */
	public function deserialize(array $data, string $target): Option;

	public function getRaisedErrors(): FieldErrors;
}
