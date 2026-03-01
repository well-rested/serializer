<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;

/**
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
abstract class Collection implements IteratorAggregate
{
	/** @var array<int, T> */
	protected array $items;

	public function __construct()
	{
		$this->items = [];
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	/** @param T $items */
	public function add(mixed ...$items): static
	{
		foreach ($items as $item) {
			if (!static::isType($item)) {
				throw new InvalidArgumentException('incorrect type for ' . static::class);
			}
		}

		$this->items = array_merge($this->items, array_values($items));

		return $this;
	}

	/** @return ArrayIterator<int, T> */
	public function getIterator(): Iterator
	{
		return new ArrayIterator($this->items);
	}

	/** @return array<int, T> */
	public function all(): array
	{
		return $this->items;
	}

	/**
	 * @return T|null
	 */
	public function first(): mixed
	{
		return $this->items[0] ?? null;
	}

	abstract protected static function isType(mixed $value): bool;
}
