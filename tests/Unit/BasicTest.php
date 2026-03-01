<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class BasicTest extends TestCase
{
    public function test_blah(): void
    {
        $this->assertTrue(true);
    }
}