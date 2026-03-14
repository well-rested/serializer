<?php

declare(strict_types=1);

namespace Tests\Unit\Util;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Util\Fixture\StringCollection;
use WellRested\Serializer\Util\Collection;

#[CoversClass(Collection::class)]
class CollectionTest extends TestCase
{
	public function test_starts_empty(): void
	{
		$collection = new StringCollection();

		$this->assertTrue($collection->isEmpty());
		$this->assertSame([], $collection->all());
	}

	public function test_is_empty_returns_false_after_add(): void
	{
		$collection = new StringCollection();
		$collection->add('a');

		$this->assertFalse($collection->isEmpty());
	}

	public function test_add_returns_static(): void
	{
		$collection = new StringCollection();
		$result = $collection->add('a');

		$this->assertSame($collection, $result);
	}

	public function test_add_stores_single_item(): void
	{
		$collection = new StringCollection();
		$collection->add('hello');

		$this->assertSame(['hello'], $collection->all());
	}

	public function test_add_stores_multiple_items_variadically(): void
	{
		$collection = new StringCollection();
		$collection->add('a', 'b', 'c');

		$this->assertSame(['a', 'b', 'c'], $collection->all());
	}

	public function test_add_appends_on_successive_calls(): void
	{
		$collection = new StringCollection();
		$collection->add('a');
		$collection->add('b');

		$this->assertSame(['a', 'b'], $collection->all());
	}

	public function test_add_throws_for_wrong_type(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$collection = new StringCollection();
		$collection->add(42); // @phpstan-ignore-line
	}

	public function test_add_throws_if_any_item_is_wrong_type(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$collection = new StringCollection();
		$collection->add('valid', 99); // @phpstan-ignore-line
	}

	public function test_merge_combines_items(): void
	{
		$a = new StringCollection();
		$a->add('a', 'b');

		$b = new StringCollection();
		$b->add('c', 'd');

		$a->merge($b);

		$this->assertSame(['a', 'b', 'c', 'd'], $a->all());
	}

	public function test_merge_returns_static(): void
	{
		$a = new StringCollection();
		$b = new StringCollection();

		$result = $a->merge($b);

		$this->assertSame($a, $result);
	}

	public function test_merge_does_not_modify_other(): void
	{
		$a = new StringCollection();
		$a->add('a');

		$b = new StringCollection();
		$b->add('b');

		$a->merge($b);

		$this->assertSame(['b'], $b->all());
	}

	public function test_get_iterator_returns_array_iterator(): void
	{
		$collection = new StringCollection();
		$collection->add('x', 'y');

		$iterator = $collection->getIterator();

		$this->assertInstanceOf(ArrayIterator::class, $iterator);
		$this->assertSame(['x', 'y'], $iterator->getArrayCopy());
	}

	public function test_first_returns_first_item(): void
	{
		$collection = new StringCollection();
		$collection->add('first', 'second');

		$this->assertSame('first', $collection->first());
	}

	public function test_first_returns_null_when_empty(): void
	{
		$collection = new StringCollection();

		$this->assertNull($collection->first());
	}
}
