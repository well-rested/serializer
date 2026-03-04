<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use WellRested\Serializer\Analysis\ClassAnalyses;
use WellRested\Serializer\Analysis\ClassAnalysisContext;

interface ClassAnalyserInterface
{
	// TODO: Get rid of the allowsNull field here, it should only be used internally in the class
	// probably add a 'doAnalyse' method or something that is called from analyse
	public function analyse(string $class, bool $allowsNull = false, ?ClassAnalysisContext $context = null): ClassAnalyses;
}
