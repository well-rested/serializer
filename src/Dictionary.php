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
 * @implements IteratorAggregate<string, T>
 */
abstract class Dictionary implements IteratorAggregate
{
	/**
	 * @var array<string, T>
	 */
	protected array $items;

	public function __construct()
	{
		$this->items = [];
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	/** @param T $item */
	public function add(string $key, mixed $item): static
	{
		if (!static::isType($item)) {
			throw new InvalidArgumentException('incorrect type for ' . static::class);
		}

		$this->items[$key] = $item;

		return $this;
	}

	/**
	 * @param T|null $default
	 *
	 * @return T|null
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		return $this->items[$key] ?? $default;
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->items);
	}

	/** @param self<T> $other */
	public function merge(self $other): static
	{
		foreach ($other as $key => $value) {
			$this->add($key, $value);
		}

		return $this;
	}

	/** @return ArrayIterator<string, T> */
	public function getIterator(): Iterator
	{
		return new ArrayIterator($this->items);
	}

	abstract protected static function isType(mixed $value): bool;
}
