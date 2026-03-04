<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

class AnalysisLink
{
	public function __construct(
		protected string $className,
		protected bool $allowsNull,
	) {}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function allowsNull(): bool
	{
		return $this->allowsNull;
	}
}
