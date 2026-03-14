<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WellRested\Serializer\Exceptions\IncorrectTypeForHoistException;

#[CoversClass(IncorrectTypeForHoistException::class)]
class IncorrectTypeForHoistExceptionTest extends TestCase
{
	public function test_extends_exception(): void
	{
		$e = new IncorrectTypeForHoistException('Container', 'path', 'string');

		$this->assertInstanceOf(Exception::class, $e);
	}

	public function test_message_format(): void
	{
		$e = new IncorrectTypeForHoistException('MyClass', 'some.path', 'integer');

		$this->assertSame(
			'property with hoist attribute was incorrect type given: integer, for MyClass->some.path',
			$e->getMessage(),
		);
	}

	public function test_code_is_forwarded(): void
	{
		$e = new IncorrectTypeForHoistException('A', 'b', 'c', 7);

		$this->assertSame(7, $e->getCode());
	}

	public function test_previous_is_forwarded(): void
	{
		$previous = new RuntimeException('root cause');
		$e = new IncorrectTypeForHoistException('A', 'b', 'c', 0, $previous);

		$this->assertSame($previous, $e->getPrevious());
	}
}
