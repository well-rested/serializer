<?php

declare(strict_types=1);

namespace Tests\Unit\Analysis;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Unit\Analysis\Fixture\Defaults\HasDefaults;
use Tests\Unit\Analysis\Fixture\FieldAttribute\OverridesSerializedNames;
use Tests\Unit\Analysis\Fixture\FieldAttribute\SettingTypeViaFieldAttr;
use Tests\Unit\Analysis\Fixture\NullableFields;
use Tests\Unit\Analysis\Fixture\Options\OptionalFields;
use Tests\Unit\Analysis\Fixture\Options\SubField;
use Tests\Unit\Analysis\Fixture\Polymorphism\DummyInterface;
use Tests\Unit\Analysis\Fixture\Polymorphism\MultiTypesDummy;
use Tests\Unit\Analysis\Fixture\Recursion\InvalidRecursionChild;
use Tests\Unit\Analysis\Fixture\Recursion\InvalidRecursionRoot;
use Tests\Unit\Analysis\Fixture\Recursion\InvalidRecursionSelf;
use Tests\Unit\Analysis\Fixture\Recursion\InvalidRecursionWithMiddleManBottom;
use Tests\Unit\Analysis\Fixture\Recursion\InvalidRecursionWithMiddleManMiddleMan;
use Tests\Unit\Analysis\Fixture\Recursion\InvalidRecursionWithMiddleManRoot;
use Tests\Unit\Analysis\Fixture\Recursion\RecursionSelf;
use Tests\Unit\Analysis\Fixture\Recursion\RecursionWithMiddleManBottom;
use Tests\Unit\Analysis\Fixture\Recursion\RecursionWithMiddleManMiddleMan;
use Tests\Unit\Analysis\Fixture\Recursion\RecursionWithMiddleManRoot;
use Tests\Unit\Analysis\Fixture\Recursion\RecursiveChildDummy;
use Tests\Unit\Analysis\Fixture\Recursion\RecursiveDummy;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\AllPromoted;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\AllPromotedReadOnlyClass;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\AllPromotedReadOnlyProperties;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\AllPublic;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\AllSetViaAttribute;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\PrivateSetNoSetViaAttribute;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\ProtectedSetNoSetViaAttribute;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\PublicReadOnlyClass;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\PublicReadOnlyProperty;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\SetViaIncorrectArgType;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\SetViaMissingMethod;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\SetViaNonPublicMethod;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\SetViaReadOnlyClass;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\SetViaReadOnlyProperty;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\SetViaTooManyArgs;
use Tests\Unit\Analysis\Fixture\SetPropertyStrategy\SetViaZeroArgs;
use Tests\Unit\Analysis\Fixture\SimpleDummy;
use Tests\Unit\Analysis\Fixture\SnakeCaseNamingStrategy;
use WellRested\Serializer\Analysis\Attributes;
use WellRested\Serializer\Analysis\ClassAnalyser;
use WellRested\Serializer\Analysis\ClassAnalyses;
use WellRested\Serializer\Analysis\ClassAnalysis;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Analysis\HoistStrategy;
use WellRested\Serializer\Analysis\PropertyAnalyses;
use WellRested\Serializer\Analysis\PropertyAnalysis;
use WellRested\Serializer\Analysis\PropertyTypeName;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
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
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionFactory;
use WellRested\Serializer\Analysis\TypeDefinitions\UnionTypeDefinition;
use WellRested\Serializer\Attributes\Field;
use WellRested\Serializer\Attributes\SetVia;

#[CoversClass(ClassAnalyser::class)]
class ClassAnalyserTest extends TestCase
{
	protected ?ClassAnalyser $analyser;

	/**
	 * Note we use the SnakeCaseNamingStrategy throughout this. Every test covers this to some degree as it is used to
	 * generate the serialized name (in the absence of a relevant Field attribute).
	 */
	public function setUp(): void
	{
		parent::setUp();

		$this->analyser = new ClassAnalyser(
			new SnakeCaseNamingStrategy(),
			new TypeDefinitionFactory(),
		);
	}

	/**
	 * Ensure that if the class does not exist we just bail out early.
	 */
	public function testClassMustExist(): void
	{
		$this->expectExceptionObject(new InvalidArgumentException('value is not a class: \not\a\class\brah'));
		$this->analyser->analyse('\not\a\class\brah');
	}

	protected function expectedSimpleDummyAnalysis(): ClassAnalysis
	{
		return new ClassAnalysis(
			name: SimpleDummy::class,
			attributes: new Attributes(),
			properties: (new PropertyAnalyses())
				->add('someInt', new PropertyAnalysis(
					name: 'someInt',
					serializedName: 'some_int',
					type: PropertyTypeName::Int,
					possibleConcreteTypes: [
						PropertyTypeName::Int->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new IntegerTypeDefinition(),
				))
				->add('someString', new PropertyAnalysis(
					name: 'someString',
					serializedName: 'some_string',
					type: PropertyTypeName::String,
					possibleConcreteTypes: [
						PropertyTypeName::String->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new StringTypeDefinition(),
				))
				->add('someBool', new PropertyAnalysis(
					name: 'someBool',
					serializedName: 'some_bool',
					type: PropertyTypeName::Bool,
					possibleConcreteTypes: [
						PropertyTypeName::Bool->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new BoolTypeDefinition(),
				))
				->add('someFloat', new PropertyAnalysis(
					name: 'someFloat',
					serializedName: 'some_float',
					type: PropertyTypeName::Float,
					possibleConcreteTypes: [
						PropertyTypeName::Float->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new FloatTypeDefinition(),
				))
				->add('someArray', new PropertyAnalysis(
					name: 'someArray',
					serializedName: 'some_array',
					type: PropertyTypeName::Array,
					possibleConcreteTypes: [
						PropertyTypeName::Any->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new ArrayTypeDefinition(new MixedTypeDefinition()),
				))
				->add('someNull', new PropertyAnalysis(
					name: 'someNull',
					serializedName: 'some_null',
					type: PropertyTypeName::Null,
					possibleConcreteTypes: [
						PropertyTypeName::Null->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new NullTypeDefinition(),
				))
				->add('someMixed', new PropertyAnalysis(
					name: 'someMixed',
					serializedName: 'some_mixed',
					type: PropertyTypeName::Mixed,
					possibleConcreteTypes: [
						PropertyTypeName::Mixed->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new MixedTypeDefinition(),
				))
				->add('someObject', new PropertyAnalysis(
					name: 'someObject',
					serializedName: 'some_object',
					type: PropertyTypeName::Object,
					possibleConcreteTypes: [
						PropertyTypeName::Object->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: false,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new ObjectTypeDefinition(),
				))
				->add('someNoType', new PropertyAnalysis(
					name: 'someNoType',
					serializedName: 'some_no_type',
					type: PropertyTypeName::Any,
					possibleConcreteTypes: [
						PropertyTypeName::Any->value,
					],
					setterStrategy: new SetPropertyStrategy(
						method: SetPropertyStrategyMethod::PublicSetter,
					),
					getterStrategy: new GetPropertyStrategy(
						method: GetPropertyStrategyMethod::PublicGetter,
					),
					hasDefault: true,
					defaultValue: null,
					attributes: new Attributes(),
					hoistStrategy: new HoistStrategy(
						enabled: false,
					),
					_type: new MixedTypeDefinition(),
				)),
		);
	}

	/**
	 * Check that a simple class with nothing but scalar types is correctly analysed.
	 */
	public function testSimpleDummy(): void
	{
		$analyses = $this->analyser->analyse(SimpleDummy::class);

		$expected = (new ClassAnalyses())
			->add(SimpleDummy::class, $this->expectedSimpleDummyAnalysis());

		$this->assertEquals($expected, $analyses);
	}

	public function testDefaults(): void
	{
		$analyses = $this->analyser->analyse(HasDefaults::class);

		$expected = (new ClassAnalyses())
			->add(HasDefaults::class, new ClassAnalysis(
				name: HasDefaults::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('myInt', new PropertyAnalysis(
						name: 'myInt',
						serializedName: 'my_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: true,
						defaultValue: 1234,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntegerTypeDefinition(),
					))
					->add('myString', new PropertyAnalysis(
						name: 'myString',
						serializedName: 'my_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::SetterMethod,
							setterMethod: 'setMyString',
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: true,
						defaultValue: 'meh',
						attributes: (new Attributes())
							->add(new SetVia(method: 'setMyString')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					))
					->add('id', new PropertyAnalysis(
						name: 'id',
						serializedName: 'id',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 0,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: true,
						defaultValue: 'blah',
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	public function testNullableFields(): void
	{
		$analyses = $this->analyser->analyse(NullableFields::class);

		$expected = (new ClassAnalyses())
			->add(NullableFields::class, new ClassAnalysis(
				name: NullableFields::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someInt', new PropertyAnalysis(
						name: 'someInt',
						serializedName: 'some_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
							PropertyTypeName::Null->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new UnionTypeDefinition(new IntegerTypeDefinition(), new NullTypeDefinition()),
					))
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
							PropertyTypeName::Null->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new UnionTypeDefinition(new StringTypeDefinition(), new NullTypeDefinition()),
					))
					->add('someArray', new PropertyAnalysis(
						name: 'someArray',
						serializedName: 'some_array',
						type: PropertyTypeName::Array,
						possibleConcreteTypes: [
							PropertyTypeName::Any->value,
							PropertyTypeName::Null->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new UnionTypeDefinition(new ArrayTypeDefinition(new MixedTypeDefinition()), new NullTypeDefinition()),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * Ensure that we correctly define the concreete types of a union or intersection type.
	 */
	public function testMultiTypes(): void
	{
		$analyses = $this->analyser->analyse(MultiTypesDummy::class);

		$expected = (new ClassAnalyses())
			->add(MultiTypesDummy::class, new ClassAnalysis(
				name: MultiTypesDummy::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('union', new PropertyAnalysis(
						name: 'union',
						serializedName: 'union',
						type: PropertyTypeName::Union,
						possibleConcreteTypes: [
							SimpleDummy::class,
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new UnionTypeDefinition(new ClassTypeDefinition(fqdn: SimpleDummy::class), new StringTypeDefinition()),
					))
					->add('intersection', new PropertyAnalysis(
						name: 'intersection',
						serializedName: 'intersection',
						type: PropertyTypeName::Intersection,
						possibleConcreteTypes: [
							DummyInterface::class,
							SimpleDummy::class,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntersectionTypeDefinition(
							new InterfaceTypeDefinition(fqdn: DummyInterface::class),
							new ClassTypeDefinition(fqdn: SimpleDummy::class),
						),
					)),
			))
			->add(SimpleDummy::class, $this->expectedSimpleDummyAnalysis());

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * Ensure that we can use the `#[Field(name: '...')]` attribute to override the name of a property in the serialized
	 * target.
	 */
	public function testOverridingSerializedName(): void
	{
		$analyses = $this->analyser->analyse(OverridesSerializedNames::class);

		$expected = (new ClassAnalyses())
			->add(OverridesSerializedNames::class, new ClassAnalysis(
				name: OverridesSerializedNames::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someInt', new PropertyAnalysis(
						name: 'someInt',
						serializedName: 'super_special_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field('super_special_int')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntegerTypeDefinition(),
					))
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'someRandomString',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field('someRandomString')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					))
					->add('someOtherString', new PropertyAnalysis(
						name: 'someOtherString',
						serializedName: 'complete_garbage_name',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field('complete_garbage_name')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * Ensure that we set the possible concrete types for an array, based on the
	 * field attribute.
	 */
	public function testSettingTypeViaFieldAttribute(): void
	{
		$analyses = $this->analyser->analyse(SettingTypeViaFieldAttr::class);

		$expected = (new ClassAnalyses())
			->add(SettingTypeViaFieldAttr::class, new ClassAnalysis(
				name: SettingTypeViaFieldAttr::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someStrings', new PropertyAnalysis(
						name: 'someStrings',
						serializedName: 'some_strings',
						type: PropertyTypeName::Array,
						possibleConcreteTypes: [
							PropertyTypeName::String->value . '[]',
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: 'string[]')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new ArrayTypeDefinition(new StringTypeDefinition()),
					))
					->add('someDates', new PropertyAnalysis(
						name: 'someDates',
						serializedName: 'some_dates',
						type: PropertyTypeName::Array,
						possibleConcreteTypes: [
							// TODO: all kindsa broken but we're removing this...
							'(int',
							'string)[]',
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: '(int|string)[]')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new ArrayTypeDefinition(
							new UnionTypeDefinition(
								new IntegerTypeDefinition(),
								new StringTypeDefinition(),
							),
						),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * Test a recursive hierarchy. Mainly we just wanna make sure that we don't introduce any infinite loops with this
	 * test, the analyser can use it's context to see if a class property is also one of it's parents and prevent
	 * analysing that class again.
	 */
	public function testRecursionWithValidHierarchy(): void
	{
		$analyses = $this->analyser->analyse(RecursiveDummy::class);

		$expected = (new ClassAnalyses())
			->add(RecursiveDummy::class, new ClassAnalysis(
				name: RecursiveDummy::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('child', new PropertyAnalysis(
						name: 'child',
						serializedName: 'child',
						type: PropertyTypeName::Complex,
						possibleConcreteTypes: [
							RecursiveChildDummy::class,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new ClassTypeDefinition(fqdn: RecursiveChildDummy::class),
					)),
			))
			->add(RecursiveChildDummy::class, new ClassAnalysis(
				name: RecursiveChildDummy::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('parent', new PropertyAnalysis(
						name: 'parent',
						serializedName: 'parent',
						type: PropertyTypeName::Complex,
						possibleConcreteTypes: [
							RecursiveDummy::class,
							PropertyTypeName::Null->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new UnionTypeDefinition(
							new ClassTypeDefinition(fqdn: RecursiveDummy::class),
							new NullTypeDefinition(),
						),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * @see testRecursiveHierarchy
	 *
	 * This one has a class in between the two recursive components...but the middle man is nullable.
	 *
	 * ClassA <---------------|
	 * 		ClassB $prop1     |
	 * ClassB                 |
	 * 		ClassC $prop2     |
	 * ClassC                 |
	 * 		ClassA $prop3 ->--|
	 */
	public function testRecursionWithValidHierarchyWithMiddleMan(): void
	{
		$analyses = $this->analyser->analyse(RecursionWithMiddleManRoot::class);

		$expected = (new ClassAnalyses())
			->add(RecursionWithMiddleManRoot::class, new ClassAnalysis(
				name: RecursionWithMiddleManRoot::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('middle', new PropertyAnalysis(
						name: 'middle',
						serializedName: 'middle',
						type: PropertyTypeName::Complex,
						possibleConcreteTypes: [
							RecursionWithMiddleManMiddleMan::class,
							PropertyTypeName::Null->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new UnionTypeDefinition(
							new ClassTypeDefinition(fqdn: RecursionWithMiddleManMiddleMan::class),
							new NullTypeDefinition(),
						),
					)),
			))
			->add(RecursionWithMiddleManMiddleMan::class, new ClassAnalysis(
				name: RecursionWithMiddleManMiddleMan::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('child', new PropertyAnalysis(
						name: 'child',
						serializedName: 'child',
						type: PropertyTypeName::Complex,
						possibleConcreteTypes: [
							RecursionWithMiddleManBottom::class,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new ClassTypeDefinition(fqdn: RecursionWithMiddleManBottom::class),
					)),
			))
			->add(RecursionWithMiddleManBottom::class, new ClassAnalysis(
				name: RecursionWithMiddleManBottom::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('top', new PropertyAnalysis(
						name: 'top',
						serializedName: 'top',
						type: PropertyTypeName::Complex,
						possibleConcreteTypes: [
							RecursionWithMiddleManRoot::class,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new ClassTypeDefinition(fqdn: RecursionWithMiddleManRoot::class),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	public function testRecursionWithSelf(): void
	{
		$analyses = $this->analyser->analyse(RecursionSelf::class);

		$expected = (new ClassAnalyses())
			->add(RecursionSelf::class, new ClassAnalysis(
				name: RecursionSelf::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('anotherMe', new PropertyAnalysis(
						name: 'anotherMe',
						serializedName: 'another_me',
						type: PropertyTypeName::Complex,
						possibleConcreteTypes: [
							RecursionSelf::class,
							PropertyTypeName::Null->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new UnionTypeDefinition(
							new ClassTypeDefinition(fqdn: RecursionSelf::class),
							new NullTypeDefinition(),
						),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * Ensure we're preventing infinitely recursive data structures. So if ClassA::prop1 is type ClassB, and ClassB has
	 * prop2 of type ClassA we'll never be abel to construct the hierarchy. We require that either of these properties
	 * allows null, so ClassA::prop1 must be `?ClassB` or `ClassB|null`; or ClassB::prop2 must of `?ClassA` or
	 * `ClassA|null`.
	 */
	public function testRecursionInvalidHierarchy(): void
	{
		$this->expectExceptionObject(new RuntimeException('Infinite recursion found in class: ' . InvalidRecursionRoot::class));
		$this->analyser->analyse(InvalidRecursionRoot::class);
	}

	/**
	 * Ensure we're preventing infinitely recursive data structures. So if ClassA::prop1 is type ClassB, and ClassB has
	 * prop2 of type ClassA we'll never be abel to construct the hierarchy. We require that either of these properties
	 * allows null, so ClassA::prop1 must be `?ClassB` or `ClassB|null`; or ClassB::prop2 must of `?ClassA` or
	 * `ClassA|null`.
	 */
	public function testRecursionInvalidSelfReference(): void
	{
		$this->expectExceptionObject(new RuntimeException('Infinite recursion found in class: ' . InvalidRecursionSelf::class));
		$this->analyser->analyse(InvalidRecursionSelf::class);
	}

	/**
	 * @see testInvalidRecursionRoot
	 *
	 * This is just the inverse where we start with the child.
	 */
	public function testRecursionInvalidHierarchyFromChild(): void
	{
		$this->expectExceptionObject(new RuntimeException('Infinite recursion found in class: ' . InvalidRecursionChild::class));
		$this->analyser->analyse(InvalidRecursionChild::class);
	}

	/**
	 * @see testInvalidRecursionRoot
	 *
	 * This scenario covers a hierarchy like below where the recursion is not simply one level but begins further down
	 * the chain.
	 *
	 * ClassA <---------------|
	 * 		ClassB $prop1     |
	 * ClassB                 |
	 * 		ClassC $prop2     |
	 * ClassC                 |
	 * 		ClassA $prop3 ->--|
	 */
	public function testRecursionInvalidWithMiddleMan(): void
	{
		$this->expectExceptionObject(new RuntimeException('Infinite recursion found in class: ' . InvalidRecursionWithMiddleManRoot::class));
		$this->analyser->analyse(InvalidRecursionWithMiddleManRoot::class);
	}

	/**
	 * @see testInvalidRecursionWithMiddleMan
	 *
	 * Starting in the middle here.
	 */
	public function testRecursionInvalidWithMiddleManStartAtMiddleMan(): void
	{
		$this->expectExceptionObject(new RuntimeException('Infinite recursion found in class: ' . InvalidRecursionWithMiddleManMiddleMan::class));
		$this->analyser->analyse(InvalidRecursionWithMiddleManMiddleMan::class);
	}

	/**
	 * @see testInvalidRecursionWithMiddleMan
	 *
	 * Starting at the bottom here.
	 */
	public function testRecursionInvalidWithMiddleManStartABottom(): void
	{
		$this->expectExceptionObject(new RuntimeException('Infinite recursion found in class: ' . InvalidRecursionWithMiddleManBottom::class));
		$this->analyser->analyse(InvalidRecursionWithMiddleManBottom::class);
	}

	/**
	 * Simple scenario for setter strategy where all properties are promoted. Mainly just need to check that the position
	 * of the arguments is defined correctly in the setterStrategy.
	 */
	public function testSetPropertyStrategyAllPromoted(): void
	{
		$analyses = $this->analyser->analyse(AllPromoted::class);

		$expected = (new ClassAnalyses())
			->add(AllPromoted::class, new ClassAnalysis(
				name: AllPromoted::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 0,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					))
					->add('someBool', new PropertyAnalysis(
						name: 'someBool',
						serializedName: 'some_bool',
						type: PropertyTypeName::Bool,
						possibleConcreteTypes: [
							PropertyTypeName::Bool->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 1,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new BoolTypeDefinition(),
					))
					->add('someInt', new PropertyAnalysis(
						name: 'someInt',
						serializedName: 'some_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 2,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntegerTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * @see testSetPropertyStrategyAllPromoted
	 *
	 * Basically the same but this time they're readonly (which is fine when they're using constructor promotion).
	 */
	public function testSetPropertyStrategyAllPromotedReadOnlyProperties(): void
	{
		$analyses = $this->analyser->analyse(AllPromotedReadOnlyProperties::class);

		$expected = (new ClassAnalyses())
			->add(AllPromotedReadOnlyProperties::class, new ClassAnalysis(
				name: AllPromotedReadOnlyProperties::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 0,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					))
					->add('someBool', new PropertyAnalysis(
						name: 'someBool',
						serializedName: 'some_bool',
						type: PropertyTypeName::Bool,
						possibleConcreteTypes: [
							PropertyTypeName::Bool->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 1,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new BoolTypeDefinition(),
					))
					->add('someInt', new PropertyAnalysis(
						name: 'someInt',
						serializedName: 'some_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 2,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntegerTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * @see testSetPropertyStrategyAllPromoted
	 *
	 * Basically the same but this time the class is readonly (which is fine when they're using constructor promotion).
	 */
	public function testSetPropertyStrategyAllPromotedReadOnlyClass(): void
	{
		$analyses = $this->analyser->analyse(AllPromotedReadOnlyClass::class);

		$expected = (new ClassAnalyses())
			->add(AllPromotedReadOnlyClass::class, new ClassAnalysis(
				name: AllPromotedReadOnlyClass::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 0,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					))
					->add('someBool', new PropertyAnalysis(
						name: 'someBool',
						serializedName: 'some_bool',
						type: PropertyTypeName::Bool,
						possibleConcreteTypes: [
							PropertyTypeName::Bool->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 1,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new BoolTypeDefinition(),
					))
					->add('someInt', new PropertyAnalysis(
						name: 'someInt',
						serializedName: 'some_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::ConstructorArgument,
							constructorIndex: 2,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntegerTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * All the properties are protected/private and have a relevant SetVia attribute.
	 */
	public function testSetPropertyStrategyAllSetViaAttribute(): void
	{
		$analyses = $this->analyser->analyse(AllSetViaAttribute::class);

		$expected = (new ClassAnalyses())
			->add(AllSetViaAttribute::class, new ClassAnalysis(
				name: AllSetViaAttribute::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::SetterMethod,
							setterMethod: 'setSomeString',
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new SetVia(method: 'setSomeString')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					))
					->add('someBool', new PropertyAnalysis(
						name: 'someBool',
						serializedName: 'some_bool',
						type: PropertyTypeName::Bool,
						possibleConcreteTypes: [
							PropertyTypeName::Bool->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::SetterMethod,
							setterMethod: 'setSomeBool',
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new SetVia(method: 'setSomeBool')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new BoolTypeDefinition(),
					))
					->add('someInt', new PropertyAnalysis(
						name: 'someInt',
						serializedName: 'some_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::SetterMethod,
							setterMethod: 'setSomeInt',
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::NotAvailable,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new SetVia(method: 'setSomeInt')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntegerTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * All the properties are public and there is no constructor or setVia attribute.
	 */
	public function testSetPropertyStrategyAllPublic(): void
	{
		$analyses = $this->analyser->analyse(AllPublic::class);

		$expected = (new ClassAnalyses())
			->add(AllPublic::class, new ClassAnalysis(
				name: AllPublic::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					))
					->add('someBool', new PropertyAnalysis(
						name: 'someBool',
						serializedName: 'some_bool',
						type: PropertyTypeName::Bool,
						possibleConcreteTypes: [
							PropertyTypeName::Bool->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new BoolTypeDefinition(),
					))
					->add('someInt', new PropertyAnalysis(
						name: 'someInt',
						serializedName: 'some_int',
						type: PropertyTypeName::Int,
						possibleConcreteTypes: [
							PropertyTypeName::Int->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new IntegerTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * If the property is not promoted, then it cannot be readonly. This is because we have no way of setting the property
	 * as the class is already constructed by the time the setter is called.
	 */
	public function testSetPropertyStrategyPublicReadOnlyProperty(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'property or class is readonly so all properties be promoted via constructor: ' . PublicReadOnlyProperty::class . '->someString',
			),
		);
		$this->analyser->analyse(PublicReadOnlyProperty::class);
	}

	/**
	 * @see testSetPropertyStrategyPublicReadOnlyProperty
	 *
	 * Same again, but the class is readonly rather than the property.
	 */
	public function testSetPropertyStrategyPublicReadOnlyClass(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'property or class is readonly so all properties be promoted via constructor: ' . PublicReadOnlyClass::class . '->someString',
			),
		);
		$this->analyser->analyse(PublicReadOnlyClass::class);
	}

	/**
	 * @see testSetPropertyStrategyPublicReadOnlyProperty
	 *
	 * Same rules apply for properties using a setter method.
	 */
	public function testSetPropertyStrategySetViaReadOnlyClass(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'property or class is readonly so all properties be promoted via constructor: ' . SetViaReadOnlyClass::class . '->someString',
			),
		);
		$this->analyser->analyse(SetViaReadOnlyClass::class);
	}

	/**
	 * @see testSetPropertyStrategyPublicReadOnlyProperty
	 *
	 * Same rules apply for properties using a setter method.
	 */
	public function testSetPropertyStrategySetViaReadOnlyProperty(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'property or class is readonly so all properties be promoted via constructor: ' . SetViaReadOnlyProperty::class . '->someString',
			),
		);
		$this->analyser->analyse(SetViaReadOnlyProperty::class);
	}

	/**
	 * If using a public protected(set) property and there is no set via attribute specified, and it's not a promoted
	 * property then there is no valid way for us to set the property. We should get NotAvailable.
	 */
	public function testSetPropertyStrategyProtectedSetNoSetViaAttribute(): void
	{
		$analyses = $this->analyser->analyse(ProtectedSetNoSetViaAttribute::class);

		$expected = (new ClassAnalyses())
			->add(ProtectedSetNoSetViaAttribute::class, new ClassAnalysis(
				name: ProtectedSetNoSetViaAttribute::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::NotAvailable,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * If using a public protected(set) property and there is no set via attribute specified, and it's not a promoted
	 * property then there is no valid way for us to set the property.
	 */
	public function testSetPropertyStrategyPrivateSetNoSetViaAttribute(): void
	{
		$analyses = $this->analyser->analyse(PrivateSetNoSetViaAttribute::class);

		$expected = (new ClassAnalyses())
			->add(PrivateSetNoSetViaAttribute::class, new ClassAnalysis(
				name: PrivateSetNoSetViaAttribute::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('someString', new PropertyAnalysis(
						name: 'someString',
						serializedName: 'some_string',
						type: PropertyTypeName::String,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::NotAvailable,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: new Attributes(),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new StringTypeDefinition(),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}

	/**
	 * If using the SetVia attribute, but it's pointing to a method that doesn't exist on the class, we should get an
	 * exception.
	 */
	public function testSetPropertyStrategySetViaMissingMethod(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'method defined in SetVia attribute not found on class for property: ' . SetViaMissingMethod::class . '->someString',
			),
		);
		$this->analyser->analyse(SetViaMissingMethod::class);
	}

	/**
	 * If using the SetVia attribute, but it's pointing to a method that is not public, we should get an exception.
	 */
	public function testSetPropertyStrategySetViaNonPublicMethod(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'method defined in SetVia attribute is not public, analysing: ' . SetViaNonPublicMethod::class . '->someString',
			),
		);
		$this->analyser->analyse(SetViaNonPublicMethod::class);
	}

	/**
	 * When using SetVia attribute the setter must only accept one argument.
	 */
	public function testSetPropertyStrategySetViaTooManyArgs(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'invalid number of args for setter method, found 2 (expected exactly 1) : ' . SetViaTooManyArgs::class . '->someString',
			),
		);
		$this->analyser->analyse(SetViaTooManyArgs::class);
	}

	/**
	 * When using SetVia attribute the setter must only accept one argument.
	 */
	public function testSetPropertyStrategySetViaZeroArgs(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'invalid number of args for setter method, found 0 (expected exactly 1) : ' . SetViaZeroArgs::class . '->someString',
			),
		);
		$this->analyser->analyse(SetViaZeroArgs::class);
	}

	/**
	 * When using SetVia attribute the setters argument must have the same type as the property.
	 *
	 * @TODO: consider the logic in the class, we need to look at union/intersection types, and no type.
	 */
	public function testSetPropertyStrategySetViaIncorrectArgType(): void
	{
		$this->expectExceptionObject(
			new RuntimeException(
				'only argument to setter method, must match type of property: ' . SetViaIncorrectArgType::class . '->someString',
			),
		);
		$this->analyser->analyse(SetViaIncorrectArgType::class);
	}

	public function testOptionalFields(): void
	{
		$analyses = $this->analyser->analyse(OptionalFields::class);
		$expected = (new ClassAnalyses())
			->add(OptionalFields::class, new ClassAnalysis(
				name: OptionalFields::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('email', new PropertyAnalysis(
						name: 'email',
						serializedName: 'email',
						type: PropertyTypeName::Option,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: 'string')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new OptionTypeDefinition(
							new StringTypeDefinition(),
						),
					))
					->add('firstName', new PropertyAnalysis(
						name: 'firstName',
						serializedName: 'first_name',
						type: PropertyTypeName::Option,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: 'string')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new OptionTypeDefinition(
							new StringTypeDefinition(),
						),
					))
					->add('lastName', new PropertyAnalysis(
						name: 'lastName',
						serializedName: 'last_name',
						type: PropertyTypeName::Option,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: 'string')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new OptionTypeDefinition(
							new StringTypeDefinition(),
						),
					))
					->add('sub', new PropertyAnalysis(
						name: 'sub',
						serializedName: 'sub',
						type: PropertyTypeName::Option,
						possibleConcreteTypes: [
							SubField::class,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: SubField::class)),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new OptionTypeDefinition(
							new ClassTypeDefinition(fqdn: SubField::class),
						),
					)),
			))
			->add(SubField::class, new ClassAnalysis(
				name: SubField::class,
				attributes: new Attributes(),
				properties: (new PropertyAnalyses())
					->add('line1', new PropertyAnalysis(
						name: 'line1',
						serializedName: 'line1',
						type: PropertyTypeName::Option,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: 'string')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new OptionTypeDefinition(
							new StringTypeDefinition(),
						),
					))
					->add('line2', new PropertyAnalysis(
						name: 'line2',
						serializedName: 'line2',
						type: PropertyTypeName::Option,
						possibleConcreteTypes: [
							PropertyTypeName::String->value,
						],
						setterStrategy: new SetPropertyStrategy(
							method: SetPropertyStrategyMethod::PublicSetter,
						),
						getterStrategy: new GetPropertyStrategy(
							method: GetPropertyStrategyMethod::PublicGetter,
						),
						hasDefault: false,
						defaultValue: null,
						attributes: (new Attributes())
							->add(new Field(type: 'string')),
						hoistStrategy: new HoistStrategy(
							enabled: false,
						),
						_type: new OptionTypeDefinition(
							new StringTypeDefinition(),
						),
					)),
			));

		$this->assertEquals($expected, $analyses);
	}
}
