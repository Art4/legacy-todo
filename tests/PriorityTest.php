<?php

namespace LegacyTodo\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../functions.php';

class PriorityTest extends TestCase
{
    public function testOneMapsToOne()
    {
        $this->assertSame(1, normalisePriority(1));
    }

    public function testTwoMapsToTwo()
    {
        $this->assertSame(2, normalisePriority(2));
    }

    public function testThreeMapsToThree()
    {
        $this->assertSame(3, normalisePriority(3));
    }

    public function testAnythingAboveTwoMapsToThree()
    {
        $this->assertSame(3, normalisePriority(5));
    }

    public function testLooseEqualityStringTwoMapsToTwo()
    {
        $this->assertSame(2, normalisePriority("2"));
    }
}
