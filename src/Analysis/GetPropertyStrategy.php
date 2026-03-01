<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use RuntimeException;

class GetPropertyStrategy
{
	public function __construct(
		protected GetPropertyStrategyMethod $method,
		protected ?string $getterMethod = null,
	) {}

	public function getMethod(): GetPropertyStrategyMethod
	{
		return $this->method;
	}

	public function getGetterMethod(): string
	{
		if (GetPropertyStrategyMethod::GetterMethod !== $this->method) {
			throw new RuntimeException('No getterMethod for strategy: ' . (string) $this->method);
		}

		if (null === $this->getterMethod) {
			throw new RuntimeException('Invalid getter strategy, setterMethod is not set when method is ' . (string) $this->method);
		}

		return $this->getterMethod;
	}
}
