<?php

namespace LegacyTodo\Tests;

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    public function testHarnessRuns()
    {
        $this->assertTrue(true);
    }

    public function testAdditionSanity()
    {
        $this->assertSame(2, 1 + 1);
    }
}
