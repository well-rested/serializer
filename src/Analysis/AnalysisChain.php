<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis;

class AnalysisChain
{
	public function __construct(
		/** @var AnalysisLink[] */
		protected array $chain = [],
	) {}

	public function addLink(AnalysisLink $link): self
	{
		return new self(
			chain: array_merge($this->chain, [$link]),
		);
	}

	public function hasLink(string $class): bool
	{
		return null !== $this->getLink($class);
	}

	public function getLink(string $class): ?AnalysisLink
	{
		$links = array_filter(
			$this->chain,
			fn(AnalysisLink $link) => $link->getClassName() === $class,
		);

		return $links[0] ?? null;
	}

	public function root(): ?AnalysisLink
	{
		return $this->chain[0] ?? null;
	}

	public function hasANullableLink(): bool
	{
		$links = array_filter(
			$this->chain,
			fn(AnalysisLink $link) => $link->allowsNull(),
		);

		return !empty($links);
	}
}
