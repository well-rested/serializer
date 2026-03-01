<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use WellRested\Serializer\Analysis\TypeDefinitions\OptionTypeDefinition;
use WellRested\Serializer\Analysis\TypeDefinitions\TypeDefinitionAbstract;

class PropertyAnalysis
{
	public function __construct(
		protected string $name,
		protected string $serializedName,
		protected PropertyTypeName $type,
		protected array $possibleConcreteTypes,
		protected SetPropertyStrategy $setterStrategy,
		protected GetPropertyStrategy $getterStrategy,
		protected bool $hasDefault,
		protected mixed $defaultValue,
		protected HoistStrategy $hoistStrategy,
		protected Attributes $attributes,
		protected TypeDefinitionAbstract $_type,
	) {}

	public function getTypeDefinition(): TypeDefinitionAbstract
	{
		return $this->_type;
	}

	public function isOptional(): bool
	{
		return $this->_type->is(OptionTypeDefinition::class);
		// return PropertyTypeName::Option === $this->getType();
	}

	public function getSerializedName(): string
	{
		return $this->serializedName;
	}

	/**
	 * @return string[]
	 */
	public function getPossibleTypes(): array
	{
		return $this->possibleConcreteTypes;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function shouldPassThroughConstructor(): bool
	{
		return SetPropertyStrategyMethod::ConstructorArgument === $this->setterStrategy->getMethod();
	}

	public function shouldUsePublicSetter(): bool
	{
		return SetPropertyStrategyMethod::PublicSetter === $this->setterStrategy->getMethod();
	}

	public function shouldUseSetterMethod(): bool
	{
		return SetPropertyStrategyMethod::SetterMethod === $this->setterStrategy->getMethod();
	}

	public function getConstructorIndex(): int
	{
		return $this->setterStrategy->getConstructorIndex();
	}

	public function getSetterMethod(): string
	{
		return $this->setterStrategy->getSetterMethod();
	}

	public function canGetPropertyValue(): bool
	{
		return GetPropertyStrategyMethod::NotAvailable !== $this->getterStrategy->getMethod();
	}

	public function shouldUseGetterMethod(): bool
	{
		return GetPropertyStrategyMethod::GetterMethod == $this->getterStrategy->getMethod();
	}

	public function getGetterMethod(): string
	{
		return $this->getterStrategy->getGetterMethod();
	}

	public function allowsNull(): bool
	{
		return $this->_type->allowsNull();
		// return in_array(PropertyTypeName::Null->value, $this->possibleConcreteTypes);
	}

	public function hasDefault(): bool
	{
		return $this->hasDefault;
	}

	public function getDefault(): mixed
	{
		return $this->defaultValue;
	}

	public function shouldHoist(): bool
	{
		return $this->hoistStrategy->enabled();
	}

	public function hoistProperty(): string
	{
		return $this->hoistStrategy->getProperty();
	}

	public function getAttributes(): Attributes
	{
		return $this->attributes;
	}
}
