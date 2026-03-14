<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WellRested\Serializer\Exceptions\UnretrievablePropertyException;

#[CoversClass(UnretrievablePropertyException::class)]
class UnretrievablePropertyExceptionTest extends TestCase
{
	public function test_extends_exception(): void
	{
		$e = new UnretrievablePropertyException('Container', 'path');

		$this->assertInstanceOf(Exception::class, $e);
	}

	public function test_message_format(): void
	{
		$e = new UnretrievablePropertyException('MyClass', 'some.path');

		$this->assertSame(
			'property could not be retrieved: MyClass->some.path',
			$e->getMessage(),
		);
	}

	public function test_code_is_forwarded(): void
	{
		$e = new UnretrievablePropertyException('A', 'b', 5);

		$this->assertSame(5, $e->getCode());
	}

	public function test_previous_is_forwarded(): void
	{
		$previous = new RuntimeException('root cause');
		$e = new UnretrievablePropertyException('A', 'b', 0, $previous);

		$this->assertSame($previous, $e->getPrevious());
	}
}
