<?php

declare(strict_types=1);

namespace WellRested\Serializer\Errors;

use PhpOption\Option;
use RuntimeException;

class FieldError
{
	public function __construct(
		public protected(set) string $location,
		public protected(set) string $message,
		/** @var Option<mixed> */
		public protected(set) Option $value,
	) {}

	/** @return array{location: string, message: string, value?: mixed} */
	public function toArray(): array
	{
		$result = [
			'location' => $this->location,
			'message' => $this->message,
		];

		if ($this->value->isDefined()) {
			// Convert to an array, this feels a bit dodgy, but should work?
			$encoded = json_encode($this->value->get());
			if ($encoded === false) {
				throw new RuntimeException('failed to encode value');
			}

			$result['value'] = json_decode($encoded);
		}

		return $result;
	}
}
