<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WellRested\Serializer\Analysis\HoistStrategy;

#[CoversClass(HoistStrategy::class)]
class HoistStrategyTest extends TestCase
{
	public function test_enabled_returns_true(): void
	{
		$strategy = new HoistStrategy(true, 'items');

		$this->assertTrue($strategy->enabled());
	}

	public function test_enabled_returns_false(): void
	{
		$strategy = new HoistStrategy(false);

		$this->assertFalse($strategy->enabled());
	}

	public function test_get_property_returns_property_name(): void
	{
		$strategy = new HoistStrategy(true, 'items');

		$this->assertSame('items', $strategy->getProperty());
	}

	public function test_get_property_throws_when_not_enabled(): void
	{
		$strategy = new HoistStrategy(false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Hoisting is not enabled!');

		$strategy->getProperty();
	}

	public function test_get_property_throws_when_property_is_null(): void
	{
		$strategy = new HoistStrategy(true);

		$this->expectException(RuntimeException::class);

		$strategy->getProperty();
	}
}
