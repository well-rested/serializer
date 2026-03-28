<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use WellRested\Serializer\Analysis\PolymorphismStrategy;

#[CoversClass(PolymorphismStrategy::class)]
class PolymorphismStrategyTest extends TestCase
{
	public function test_enabled_returns_true(): void
	{
		$strategy = new PolymorphismStrategy(
			enabled: true,
			field: '@type',
			typeMap: ['a' => stdClass::class],
		);

		$this->assertTrue($strategy->enabled());
	}

	public function test_enabled_returns_false(): void
	{
		$strategy = new PolymorphismStrategy(enabled: false);

		$this->assertFalse($strategy->enabled());
	}

	public function test_field_returns_field_name(): void
	{
		$strategy = new PolymorphismStrategy(
			enabled: true,
			field: 'kind',
			typeMap: ['a' => stdClass::class],
		);

		$this->assertSame('kind', $strategy->field());
	}

	public function test_field_throws_when_not_enabled(): void
	{
		$strategy = new PolymorphismStrategy(enabled: false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Polymorphism is not enabled.');

		$strategy->field();
	}

	public function test_field_throws_when_field_is_null(): void
	{
		$strategy = new PolymorphismStrategy(enabled: true);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid polymorphism strategy, field is not set.');

		$strategy->field();
	}

	public function test_type_map_returns_type_map(): void
	{
		$typeMap = ['cat' => stdClass::class, 'dog' => stdClass::class];
		$strategy = new PolymorphismStrategy(
			enabled: true,
			field: '@type',
			typeMap: $typeMap,
		);

		$this->assertSame($typeMap, $strategy->typeMap());
	}

	public function test_type_map_throws_when_not_enabled(): void
	{
		$strategy = new PolymorphismStrategy(enabled: false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Polymorphism is not enabled.');

		$strategy->typeMap();
	}

	public function test_type_map_throws_when_type_map_is_null(): void
	{
		$strategy = new PolymorphismStrategy(enabled: true);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid polymorphism strategy, type map is not set.');

		$strategy->typeMap();
	}
}
