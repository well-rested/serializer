<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;

#[CoversClass(GetPropertyStrategy::class)]
class GetPropertyStrategyTest extends TestCase
{
	public function test_get_method_returns_method(): void
	{
		$strategy = new GetPropertyStrategy(GetPropertyStrategyMethod::PublicGetter);

		$this->assertSame(GetPropertyStrategyMethod::PublicGetter, $strategy->getMethod());
	}

	public function test_get_getter_method_returns_method_name(): void
	{
		$strategy = new GetPropertyStrategy(GetPropertyStrategyMethod::GetterMethod, 'getMyValue');

		$this->assertSame('getMyValue', $strategy->getGetterMethod());
	}

	public function test_get_getter_method_throws_when_strategy_is_not_getter_method(): void
	{
		$strategy = new GetPropertyStrategy(GetPropertyStrategyMethod::PublicGetter);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No getterMethod for strategy: public_getter');

		$strategy->getGetterMethod();
	}

	public function test_get_getter_method_throws_when_getter_method_is_null(): void
	{
		$strategy = new GetPropertyStrategy(GetPropertyStrategyMethod::GetterMethod);

		$this->expectException(RuntimeException::class);

		$strategy->getGetterMethod();
	}
}
