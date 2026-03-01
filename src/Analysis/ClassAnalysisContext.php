<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

class ClassAnalysisContext
{
	public function __construct(
		protected ?AnalysisChain $chain = null,
	) {
		if (null === $this->chain) {
			$this->chain = new AnalysisChain();
		}
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
