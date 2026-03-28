<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Extractors;

use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;
use Symfony\Component\TypeInfo\Type;
use WellRested\Serializer\Analysis\Extractors\Extensions\HoistStrategyExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PolymorphismExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyDefaultValueExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertyGetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\PropertySetterMethodExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\SerializedPropertyNameExtractor;
use WellRested\Serializer\Analysis\Extractors\Extensions\WrappingStrategyExtractor;
use WellRested\Serializer\Analysis\GetPropertyStrategy;
use WellRested\Serializer\Analysis\GetPropertyStrategyMethod;
use WellRested\Serializer\Analysis\HoistStrategy;
use WellRested\Serializer\Analysis\PolymorphismStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategy;
use WellRested\Serializer\Analysis\SetPropertyStrategyMethod;
use WellRested\Serializer\Analysis\WrappingStrategy;

class PropertyAnalysis
{
	public function __construct(
		protected string $name,
		protected Type $type,
		protected PropertyAnalysisExtractionExtensions $extensions,
	) {}

	public function getType(): Type
	{
		return $this->type;
	}

	/** @return Option<mixed> */
	public function getDefaultValue(): Option
	{
		$ext = $this->extensions->get(PropertyDefaultValueExtractor::EXTENSION_NAME);

		if ($ext == null) {
			return None::create();
		}

		if ($ext->get('default_exists', false) !== true || $ext->has('value') === false) {
			return None::create();
		}

		return  Some::create($ext->get('value'));
	}

	public function getSerializedPropertyName(): string
	{
		$ext = $this->extensions->get(SerializedPropertyNameExtractor::EXTENSION_NAME);

		if ($ext == null) {
			return $this->name;
		}

		$value = $ext->get('value');

		if (!is_string($value)) {
			return $this->name;
		}

		return $value;
	}

	public function getSetterStrategy(): SetPropertyStrategy
	{
		$ext = $this->extensions->get(PropertySetterMethodExtractor::EXTENSION_NAME);

		if ($ext === null) {
			return new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::NotAvailable,
			);
		}

		$value = $ext->get('value');

		if (! $value instanceof SetPropertyStrategy) {
			return new SetPropertyStrategy(
				method: SetPropertyStrategyMethod::NotAvailable,
			);
		}

		return $value;
	}

	public function getGetterStrategy(): GetPropertyStrategy
	{
		$ext = $this->extensions->get(PropertyGetterMethodExtractor::EXTENSION_NAME);

		if ($ext === null) {
			return new GetPropertyStrategy(
				method: GetPropertyStrategyMethod::NotAvailable,
			);
		}

		$value = $ext->get('value');

		if (! $value instanceof GetPropertyStrategy) {
			return new GetPropertyStrategy(
				method: GetPropertyStrategyMethod::NotAvailable,
			);
		}

		return $value;
	}

	public function getHoistStrategy(): HoistStrategy
	{
		$ext = $this->extensions->get(HoistStrategyExtractor::EXTENSION_NAME);

		if ($ext === null) {
			return new HoistStrategy(
				enabled: false,
			);
		}

		$value = $ext->get('value');

		if (! $value instanceof HoistStrategy) {
			return new HoistStrategy(
				enabled: false,
			);
		}

		return $value;
	}

	public function getWrappingStrategy(): WrappingStrategy
	{
		$ext = $this->extensions->get(WrappingStrategyExtractor::EXTENSION_NAME);

		if ($ext === null) {
			return new WrappingStrategy(
				enabled: false,
			);
		}

		$value = $ext->get('value');

		if (! $value instanceof WrappingStrategy) {
			return new WrappingStrategy(
				enabled: false,
			);
		}

		return $value;
	}

	public function getPolymorphismStrategy(): PolymorphismStrategy
	{
		$ext = $this->extensions->get(PolymorphismExtractor::EXTENSION_NAME);

		if ($ext === null) {
			return new PolymorphismStrategy(
				enabled: false,
			);
		}

		$value = $ext->get('value');

		if (! $value instanceof PolymorphismStrategy) {
			return new PolymorphismStrategy(
				enabled: false,
			);
		}

		return $value;
	}

	public function getName(): string
	{
		return $this->name;
	}
}
