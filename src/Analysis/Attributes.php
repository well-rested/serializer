<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use Attribute;
use WellRested\Serializer\Collection;
use ReflectionClass;

/**
 * @extends Collection<mixed>
 */
class Attributes extends Collection
{
	protected static function isType(mixed $value): bool
	{
		if (!is_object($value)) {
			return false;
		}

		$reflClass = new ReflectionClass($value);

		if (empty($reflClass->getAttributes(Attribute::class))) {
			return false;
		}

		return true;
	}

	/**
	 * Something wrong here...
	 *
	 * @template T
	 *
	 * @param class-string<T> $type
	 *
	 * @return Attributes<int, T>
	 */
	public function filteredByType(string $type): Attributes
	{
		$items = new Attributes();
		foreach ($this->items as $item) {
			if ($item instanceof $type) {
				$items->add($item);
			}
		}

		return $items;
	}
}
