<?php

declare(strict_types=1);

namespace Tests\Serializer\Unit\Normalizers\Traits;

use PhpOption\None;
use PhpOption\Some;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use WellRested\Serializer\Normalizers\Contracts\DenormalizerInterface;
use WellRested\Serializer\Normalizers\Traits\DenormalizesRecursively;

#[CoversTrait(DenormalizesRecursively::class)]
class DenormalizesRecursivelyTest extends TestCase
{
	private function makeSubject(): object
	{
		return new class {
			use DenormalizesRecursively;

			public function denormalize(mixed $data, mixed $type, string $path): mixed
			{
				return $this->recursivelyDenormalize($data, $type, $path);
			}
		};
	}

	public function test_throws_when_denormalizer_not_set(): void
	{
		$subject = $this->makeSubject();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('recursive denormalizer not set');

		$subject->denormalize(None::create(), new BuiltinType(TypeIdentifier::INT), 'field');
	}

	public function test_delegates_to_denormalizer_when_set(): void
	{
		$type = new BuiltinType(TypeIdentifier::INT);
		$data = Some::create(42);

		/** @var DenormalizerInterface&MockObject $denormalizer */
		$denormalizer = $this->createMock(DenormalizerInterface::class);
		$denormalizer->expects($this->once())
			->method('denormalize')
			->with($data, $type, 'some.path')
			->willReturn(42);

		$subject = $this->makeSubject();
		$subject->withDenormalizer($denormalizer);

		$this->assertSame(42, $subject->denormalize($data, $type, 'some.path'));
	}
}
