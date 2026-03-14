<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;

#[CoversClass(SetPropertyStrategy::class)]
class SetPropertyStrategyTest extends TestCase
{
	public function test_get_method_returns_method(): void
	{
		$strategy = new SetPropertyStrategy(SetPropertyStrategyMethod::PublicSetter);

		$this->assertSame(SetPropertyStrategyMethod::PublicSetter, $strategy->getMethod());
	}

	public function test_get_setter_method_returns_method_name(): void
	{
		$strategy = new SetPropertyStrategy(SetPropertyStrategyMethod::SetterMethod, null, 'setMyValue');

		$this->assertSame('setMyValue', $strategy->getSetterMethod());
	}

	public function test_get_setter_method_throws_when_strategy_is_not_setter_method(): void
	{
		$strategy = new SetPropertyStrategy(SetPropertyStrategyMethod::PublicSetter);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No setterMethod for strategy: public_setter');

		$strategy->getSetterMethod();
	}

	public function test_get_setter_method_throws_when_setter_method_is_null(): void
	{
		$strategy = new SetPropertyStrategy(SetPropertyStrategyMethod::SetterMethod);

		$this->expectException(RuntimeException::class);

		$strategy->getSetterMethod();
	}

	public function test_get_constructor_index_returns_index(): void
	{
		$strategy = new SetPropertyStrategy(SetPropertyStrategyMethod::ConstructorArgument, 2);

		$this->assertSame(2, $strategy->getConstructorIndex());
	}

	public function test_get_constructor_index_throws_when_strategy_is_not_constructor_argument(): void
	{
		$strategy = new SetPropertyStrategy(SetPropertyStrategyMethod::PublicSetter);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No constructor index for strategy: public_setter');

		$strategy->getConstructorIndex();
	}

	public function test_get_constructor_index_throws_when_index_is_null(): void
	{
		$strategy = new SetPropertyStrategy(SetPropertyStrategyMethod::ConstructorArgument);

		$this->expectException(RuntimeException::class);

		$strategy->getConstructorIndex();
	}
}
