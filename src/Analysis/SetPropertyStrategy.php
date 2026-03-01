<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use RuntimeException;

class SetPropertyStrategy
{
	public function __construct(
		protected SetPropertyStrategyMethod $method,
		protected ?int $constructorIndex = null,
		protected ?string $setterMethod = null,
	) {}

	public function getMethod(): SetPropertyStrategyMethod
	{
		return $this->method;
	}

	public function getSetterMethod(): string
	{
		if (SetPropertyStrategyMethod::SetterMethod !== $this->method) {
			throw new RuntimeException('No setterMethod for strategy: ' . (string) $this->method);
		}

		if (null === $this->setterMethod) {
			throw new RuntimeException('Invalid setter strategy, setterMethod is not set when method is ' . (string) $this->method);
		}

		return $this->setterMethod;
	}

	public function getConstructorIndex(): int
	{
		if (SetPropertyStrategyMethod::ConstructorArgument !== $this->method) {
			throw new RuntimeException('No constructor index for strategy: ' . (string) $this->method);
		}

		if (null === $this->constructorIndex) {
			throw new RuntimeException('Invalid setter strategy, constructorIndex is not set when method is ' . (string) $this->method);
		}

		return $this->constructorIndex;
	}
}
