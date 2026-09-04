<?php

declare(strict_types=1);

require_once __DIR__ . '/../../functions.php';

final class LegacySmokeTest extends PHPUnit\Framework\TestCase
{
    public function testRunnerIsFunctional(): void
    {
        $this->assertTrue(true);
    }

    public function testLegacyPureFunction(): void
    {
        $this->assertSame(6, doStuff(1, 2));
    }
}
