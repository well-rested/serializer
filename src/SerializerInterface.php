<?php

declare(strict_types=1);

namespace WellRested\Serializer;

use WellRested\Serializer\Errors\FieldErrors;
use stdClass;

interface SerializerInterface
{
	public function serialize(object $data): array|stdClass;

	public function getRaisedErrors(): FieldErrors;
}
