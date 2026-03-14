<?php

declare(strict_types=1);

namespace WellRested\Serializer\Analysis\Types;

use Symfony\Component\TypeInfo\Type;

/**
 * Extends the symfony property info component with an optional type as a first
 * class citizen. Otherwise they're pretyy painful to detect.
 *
 * See Reflector
 */
class OptionType extends Type
{
	public function __construct(
		protected Type $wrappedType,
	) {}

	public function getWrappedType(): Type
	{
		return $this->wrappedType;
	}

	public function __toString()
	{
		return "Option<" . ((string) $this->wrappedType) . ">";
	}
}
