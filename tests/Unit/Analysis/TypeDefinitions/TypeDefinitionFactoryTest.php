<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis\TypeDefinitions;

use DateTime;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Tests\Unit\Analysis\TypeDefinitions\Fixture\InterfaceA;
use Tests\Unit\Analysis\TypeDefinitions\Fixture\SubTypeA;
use Tests\Unit\Analysis\TypeDefinitions\Fixture\SubTypeB;
use Tests\Unit\Analysis\TypeDefinitions\Fixture\TraitA;
use Tests\Unit\Analysis\TypeDefinitions\Fixture\TypesContainer;
use WellRested\Serializer\Analysis\TypeDefinitions\ArrayTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\BoolTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\ClassTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\FloatTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\IntegerTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\InterfaceTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\IntersectionTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\MixedTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\NullTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\ObjectTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\OptionTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\StringTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\TraitTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionAbstract;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionFactory;
use WellRested\Serializer\Analysis\TypeDefinitions\UnionTypeDefinition;

#[CoversClass(TypeDefinitionFactory::class)]
class TypeDefinitionFactoryTest extends TestCase
{
	protected ?TypeDefinitionFactory $factory;

	public function setUp(): void
	{
		$this->factory = new TypeDefinitionFactory();
	}

	#[DataProvider('stringScenarios')]
	public function test_from_string(string $input, TypeDefinitionAbstract $expect): void
	{
		$got = $this->factory->fromString($input);

		$this->assertEquals($expect, $got);
	}

	public static function stringScenarios(): Generator
	{
		// Singular Types
		yield 'int' => ['int', new IntegerTypeDefinition()];
		yield 'bool' => ['bool', new BoolTypeDefinition()];
		yield 'float' => ['float', new FloatTypeDefinition()];
		yield 'null' => ['null', new NullTypeDefinition()];
		yield 'object' => ['object', new ObjectTypeDefinition()];
		yield 'mixed' => ['mixed', new MixedTypeDefinition()];
		yield 'string' => ['string', new StringTypeDefinition()];
		yield 'class' => [DateTime::class, new ClassTypeDefinition(fqdn: DateTime::class)];
		yield 'array' => ['array', new ArrayTypeDefinition(new MixedTypeDefinition())];

		// Singlarly typed array
		yield 'int[]' => ['int[]', new ArrayTypeDefinition(new IntegerTypeDefinition())];

		// Simple unions
		yield 'int|string|bool' => ['int|string|bool', new UnionTypeDefinition(
			new IntegerTypeDefinition(),
			new StringTypeDefinition(),
			new BoolTypeDefinition(),
		)];

		yield 'array|string|DateTime' => ['array|string|' . DateTime::class, new UnionTypeDefinition(
			new ArrayTypeDefinition(new MixedTypeDefinition()),
			new StringTypeDefinition(),
			new ClassTypeDefinition(fqdn: DateTime::class),
		)];

		// Complex unions
		yield 'int|string|int[]|(int|string)[]' => ['int|string|int[]|(int|string)[]', new UnionTypeDefinition(
			new IntegerTypeDefinition(),
			new StringTypeDefinition(),
			new ArrayTypeDefinition(new IntegerTypeDefinition()),
			new ArrayTypeDefinition(
				new UnionTypeDefinition(new IntegerTypeDefinition(), new StringTypeDefinition()),
			),
		)];

		// Simple intersections
		yield 'InvalidArgumentException&RuntimeException' => [
			InvalidArgumentException::class . '&' . RuntimeException::class,
			new IntersectionTypeDefinition(
				new ClassTypeDefinition(InvalidArgumentException::class),
				new ClassTypeDefinition(RuntimeException::class),
			),
		];

		// Comples intersections
		yield '((InvalidArgumentException&RuntimeException)|DateTime)[]|int' => [
			'((' . InvalidArgumentException::class . '&' . RuntimeException::class . ')|' . DateTime::class . ')[]|int',
			new UnionTypeDefinition(
				new ArrayTypeDefinition(
					new UnionTypeDefinition(
						new IntersectionTypeDefinition(
							new ClassTypeDefinition(InvalidArgumentException::class),
							new ClassTypeDefinition(RuntimeException::class),
						),
						new ClassTypeDefinition(DateTime::class),
					),
				),
				new IntegerTypeDefinition(),
			),
		];
	}

	#[DataProvider('reflectionPropertyScenarios')]
	public function test_from_reflection_property(string $prop, TypeDefinitionAbstract $expect): void
	{
		$reflClass = new ReflectionClass(TypesContainer::class);

		$got = $this->factory->fromReflectionProperty($reflClass->getProperty($prop));

		$this->assertEqualsCanonicalizing($expect, $got);
	}

	/**
	 * Note the ordering is important in here to get the tests passing correctly.
	 * I.e. switching the string and int around in the union for intersectionIntStringOrDateTime
	 * will explode.
	 *
	 * The order outside of here doesn't actually matter.
	 */
	public static function reflectionPropertyScenarios(): Generator
	{
		yield 'no type: <none>' => ['noType', new MixedTypeDefinition()];
		yield 'myInt: int' => ['myInt', new IntegerTypeDefinition()];
		yield 'someInterface: InterfaceA' => ['someInterface', new InterfaceTypeDefinition(InterfaceA::class)];
		yield 'someTrait: TraitA' => ['someTrait', new TraitTypeDefinition(TraitA::class)];
		yield 'nullableInt: ?int' => ['nullableInt', new UnionTypeDefinition(
			new IntegerTypeDefinition(),
			new NullTypeDefinition(),
		)];

		yield 'typedArray: (int|string)[]' => ['typedArray', new ArrayTypeDefinition(
			new UnionTypeDefinition(
				new IntegerTypeDefinition(),
				new StringTypeDefinition(),
			),
		)];

		yield 'unionStringOrInt: int|string' => [
			'unionStringOrInt',
			new UnionTypeDefinition(
				new StringTypeDefinition(),
				new IntegerTypeDefinition(),
			),
		];

		yield 'noTypeUseFieldAttribute: int[]|string[]' => [
			'noTypeUseFieldAttribute',
			new UnionTypeDefinition(
				new ArrayTypeDefinition(new IntegerTypeDefinition()),
				new ArrayTypeDefinition(new StringTypeDefinition()),
			),
		];

		yield 'intersectionIntStringOrDateTime: int|string|(SubTypeA&SubTypeB)' => [
			'intersectionIntStringOrDateTime',
			new UnionTypeDefinition(
				new IntersectionTypeDefinition(
					new ClassTypeDefinition(SubTypeB::class),
					new ClassTypeDefinition(SubTypeA::class),
				),
				new StringTypeDefinition(),
				new IntegerTypeDefinition(),
			),
		];

		yield 'simpleIntersection: SubTypeA&SubTypeB' => [
			'simpleIntersection',
			new IntersectionTypeDefinition(
				new ClassTypeDefinition(SubTypeB::class),
				new ClassTypeDefinition(SubTypeA::class),
			),
		];

		yield 'unionWithIntersection: int|string|(SubTypeA&SubTypeB)' => [
			'intersectionIntStringOrDateTime',
			new UnionTypeDefinition(
				new IntersectionTypeDefinition(
					new ClassTypeDefinition(SubTypeB::class),
					new ClassTypeDefinition(SubTypeA::class),
				),
				new StringTypeDefinition(),
				new IntegerTypeDefinition(),
			),
		];

		yield 'optionalIntArray: Option<int[]>' => [
			'optionalIntArray',
			new OptionTypeDefinition(
				new ArrayTypeDefinition(
					new IntegerTypeDefinition(),
				),
			),
		];

		yield 'complicatedOption: Option<(int|string)[]|(SubTypeA&SubTypeB|string)[]|bool|null>' => [
			'complicatedOption',
			new OptionTypeDefinition(
				new UnionTypeDefinition(
					new ArrayTypeDefinition(
						new UnionTypeDefinition(
							new IntegerTypeDefinition(),
							new StringTypeDefinition(),
						),
					),
					new ArrayTypeDefinition(
						new UnionTypeDefinition(
							new IntersectionTypeDefinition(
								new ClassTypeDefinition(SubTypeA::class),
								new ClassTypeDefinition(SubTypeB::class),
							),
							new StringTypeDefinition(),
						),
					),
					new BoolTypeDefinition(),
					new NullTypeDefinition(),
				),
			),
		];
	}
}
