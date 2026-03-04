<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

class ClassAnalysisContext
{
	protected AnalysisChain $chain;

	public function __construct(
		/** @property AnalysisChain $chain Override actual type for PHPStan as it thinks it can be null*/
		?AnalysisChain $chain = null,
	) {
		$this->chain = null === $chain ? new AnalysisChain() : $chain;
	}

	public function addLink(AnalysisLink $link): self
	{
		$newChain = $this->chain->addLink($link);

		return new self(
			chain: $newChain,
		);
	}

	public function hasLink(string $className): bool
	{
		return $this->chain->hasLink($className);
	}

	public function hasNonNullableLink(string $className): bool
	{
		$link = $this->chain->getLink($className);

		return null !== $link ? $link->allowsNull() : false;
	}

	public function rootLink(): ?AnalysisLink
	{
		return $this->chain->root();
	}

	public function hasANullableLink(): bool
	{
		return $this->chain->hasANullableLink();
	}
}
