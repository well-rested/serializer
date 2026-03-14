<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WellRested\Serializer\Exceptions\HoistTargetNotFoundException;

#[CoversClass(HoistTargetNotFoundException::class)]
class HoistTargetNotFoundExceptionTest extends TestCase
{
	public function test_extends_exception(): void
	{
		$e = new HoistTargetNotFoundException('Container', 'path', 'target');

		$this->assertInstanceOf(Exception::class, $e);
	}

	public function test_message_format(): void
	{
		$e = new HoistTargetNotFoundException('MyClass', 'nested.path', 'targetProp');

		$this->assertSame(
			'property to hoist not found, parent: MyClass->nested.path, expected target: targetProp',
			$e->getMessage(),
		);
	}

	public function test_code_is_forwarded(): void
	{
		$e = new HoistTargetNotFoundException('A', 'b', 'c', 99);

		$this->assertSame(99, $e->getCode());
	}

	public function test_previous_is_forwarded(): void
	{
		$previous = new RuntimeException('root cause');
		$e = new HoistTargetNotFoundException('A', 'b', 'c', 0, $previous);

		$this->assertSame($previous, $e->getPrevious());
	}
}
