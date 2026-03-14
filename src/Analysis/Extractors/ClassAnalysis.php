<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors;

class ClassAnalysis
{
	public function __construct(
		protected string $name,
		protected PropertyAnalyses $properties,
	) {}

	public function getName(): string
	{
		return $this->name;
	}

	public function getProperties(): PropertyAnalyses
	{
		return $this->properties;
	}
}
