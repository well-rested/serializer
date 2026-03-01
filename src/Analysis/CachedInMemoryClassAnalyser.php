<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

use WellRested\Serializer\ClassAnalyserInterface;

class CachedInMemoryClassAnalyser implements ClassAnalyserInterface
{
	protected ClassAnalyses $cache;

	public function __construct(
		protected ClassAnalyserInterface $analyser,
	) {
		$this->cache = new ClassAnalyses();
	}

	public function analyse(string $class, bool $allowsNull = false, ?ClassAnalysisContext $context = null): ClassAnalyses
	{
		if ($this->cache->has($class)) {
			return $this->cache;
		}

		$this->cache = $this->cache->merge($this->analyser->analyse($class, $allowsNull, $context));

		return $this->cache;
	}
}
