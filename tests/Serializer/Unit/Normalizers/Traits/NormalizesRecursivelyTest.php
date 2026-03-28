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
use WellRested\Serializer\Normalizers\Contracts\NormalizerInterface;
use WellRested\Serializer\Normalizers\Traits\NormalizesRecursively;

#[CoversTrait(NormalizesRecursively::class)]
class NormalizesRecursivelyTest extends TestCase
{
	private function makeSubject(): object
	{
		return new class {
			use NormalizesRecursively;

			public function normalize(mixed $data, mixed $type, string $path): mixed
			{
				return $this->recursivelyNormalize($data, $type, $path);
			}
		};
	}

	public function test_throws_when_normalizer_not_set(): void
	{
		$subject = $this->makeSubject();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('recursive normalizer not set');

		$subject->normalize(None::create(), new BuiltinType(TypeIdentifier::INT), 'field');
	}

	public function test_delegates_to_normalizer_when_set(): void
	{
		$type = new BuiltinType(TypeIdentifier::INT);
		$data = Some::create(42);

		/** @var NormalizerInterface&MockObject $normalizer */
		$normalizer = $this->createMock(NormalizerInterface::class);
		$normalizer->expects($this->once())
			->method('normalize')
			->with($data, $type, 'some.path')
			->willReturn(42);

		$subject = $this->makeSubject();
		$subject->withNormalizer($normalizer);

		$this->assertSame(42, $subject->normalize($data, $type, 'some.path'));
	}
}
