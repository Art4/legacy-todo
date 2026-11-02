<?php

declare(strict_types=1);

use Art4\LegacyTodo\Assignment;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Assignment::class)]
final class AssignmentTest extends PHPUnit\Framework\TestCase
{
    public function testAccessorsReturnTheConstructorValues(): void
    {
        $value = new Assignment(1, 2, 3, 4, 'admin');

        $this->assertSame([1, 2, 3, 4, 'admin'], [$value->id(), $value->todoId(), $value->userId(), $value->assignedBy(), $value->username()]);
    }
}
