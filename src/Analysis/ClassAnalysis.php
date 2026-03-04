<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

class ClassAnalysis
{
	public function __construct(
		protected string $name,
		protected PropertyAnalyses $properties,
		protected Attributes $attributes,
	) {}

	public function getName(): string
	{
		return $this->name;
	}

	public function getProperties(): PropertyAnalyses
	{
		return $this->properties;
	}

	public function getAttributes(): Attributes
	{
		return $this->attributes;
	}
}
