<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Analysis\Types;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Analysis\Types\OptionType;

#[CoversClass(OptionType::class)]
class OptionTypeTest extends TestCase
{
	public function test_get_wrapped_type_returns_wrapped_type(): void
	{
		$inner = new BuiltinType(TypeIdentifier::STRING);
		$type = new OptionType($inner);

		$this->assertSame($inner, $type->getWrappedType());
	}

	public function test_to_string_wraps_inner_type(): void
	{
		$type = new OptionType(new BuiltinType(TypeIdentifier::INT));

		$this->assertSame('Option<int>', (string) $type);
	}
}
