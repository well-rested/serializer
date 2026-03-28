<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Normalizers\Fixture;

class DtoWithAccessors
{
	private int $age = 0;

	public function setAge(int $age): void
	{
		$this->age = $age;
	}

	public function getAge(): int
	{
		return $this->age;
	}
}
