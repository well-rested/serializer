<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Util;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Serializer\Unit\Util\Fixture\StringDictionary;
use WellRested\Serializer\Util\Dictionary;

#[CoversClass(Dictionary::class)]
class DictionaryTest extends TestCase
{
	public function test_starts_empty(): void
	{
		$dict = new StringDictionary();

		$this->assertTrue($dict->isEmpty());
	}

	public function test_is_empty_returns_false_after_add(): void
	{
		$dict = new StringDictionary();
		$dict->add('key', 'value');

		$this->assertFalse($dict->isEmpty());
	}

	public function test_add_returns_static(): void
	{
		$dict = new StringDictionary();
		$result = $dict->add('key', 'value');

		$this->assertSame($dict, $result);
	}

	public function test_add_stores_item_under_key(): void
	{
		$dict = new StringDictionary();
		$dict->add('foo', 'bar');

		$this->assertSame('bar', $dict->get('foo'));
	}

	public function test_add_overwrites_existing_key(): void
	{
		$dict = new StringDictionary();
		$dict->add('key', 'original');
		$dict->add('key', 'updated');

		$this->assertSame('updated', $dict->get('key'));
	}

	public function test_add_throws_for_wrong_type(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$dict = new StringDictionary();
		$dict->add('key', 42); // @phpstan-ignore-line
	}

	public function test_get_returns_item_for_existing_key(): void
	{
		$dict = new StringDictionary();
		$dict->add('hello', 'world');

		$this->assertSame('world', $dict->get('hello'));
	}

	public function test_get_returns_null_by_default_for_missing_key(): void
	{
		$dict = new StringDictionary();

		$this->assertNull($dict->get('missing'));
	}

	public function test_get_returns_provided_default_for_missing_key(): void
	{
		$dict = new StringDictionary();

		$this->assertSame('fallback', $dict->get('missing', 'fallback'));
	}

	public function test_has_returns_true_for_existing_key(): void
	{
		$dict = new StringDictionary();
		$dict->add('present', 'value');

		$this->assertTrue($dict->has('present'));
	}

	public function test_has_returns_false_for_missing_key(): void
	{
		$dict = new StringDictionary();

		$this->assertFalse($dict->has('absent'));
	}

	public function test_merge_combines_entries(): void
	{
		$a = new StringDictionary();
		$a->add('x', 'one');

		$b = new StringDictionary();
		$b->add('y', 'two');

		$a->merge($b);

		$this->assertSame('one', $a->get('x'));
		$this->assertSame('two', $a->get('y'));
	}

	public function test_merge_overwrites_duplicate_keys(): void
	{
		$a = new StringDictionary();
		$a->add('key', 'original');

		$b = new StringDictionary();
		$b->add('key', 'replacement');

		$a->merge($b);

		$this->assertSame('replacement', $a->get('key'));
	}

	public function test_merge_returns_static(): void
	{
		$a = new StringDictionary();
		$b = new StringDictionary();

		$result = $a->merge($b);

		$this->assertSame($a, $result);
	}

	public function test_merge_does_not_modify_other(): void
	{
		$a = new StringDictionary();
		$a->add('a', 'one');

		$b = new StringDictionary();
		$b->add('b', 'two');

		$a->merge($b);

		$this->assertFalse($b->has('a'));
	}

	public function test_get_iterator_returns_array_iterator(): void
	{
		$dict = new StringDictionary();
		$dict->add('foo', 'bar');
		$dict->add('baz', 'qux');

		$iterator = $dict->getIterator();

		$this->assertInstanceOf(ArrayIterator::class, $iterator);
		$this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $iterator->getArrayCopy());
	}
}
