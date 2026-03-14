<?php

declare(strict_types=1);

namespace Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WellRested\Serializer\Util\MixedDictionary;
use stdClass;

#[CoversClass(MixedDictionary::class)]
class MixedDictionaryTest extends TestCase
{
	/** @return array<string, array{mixed}> */
	public static function anyValueType(): array
	{
		return [
			'string'  => ['hello'],
			'integer' => [42],
			'float'   => [3.14],
			'boolean' => [true],
			'null'    => [null],
			'array'   => [['nested']],
			'object'  => [new stdClass()],
		];
	}

	#[DataProvider('anyValueType')]
	public function test_accepts_any_value_type(mixed $value): void
	{
		$dict = new MixedDictionary();
		$dict->add('key', $value);

		$this->assertSame($value, $dict->get('key'));
	}
}
