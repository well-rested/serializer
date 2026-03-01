<?php

declare(strict_types=1);

namespace WellRested\Serializer\Errors;

use PhpOption\Option;

class FieldError
{
	public function __construct(
		public protected(set) string $location,
		public protected(set) string $message,
		public protected(set) Option $value,
	) {}

	public function toArray(): array
	{
		$result = [
			'location' => $this->location,
			'message' => $this->message,
		];

		if ($this->value->isDefined()) {
			// Convert to an array, this feels a bit dodgy, but should work?
			$result['value'] = json_decode(json_encode($this->value->get()));
		}

		return $result;
	}
}
