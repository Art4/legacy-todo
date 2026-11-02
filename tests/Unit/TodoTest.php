<?php

declare(strict_types=1);

use Art4\LegacyTodo\Todo;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Todo::class)]
final class TodoTest extends PHPUnit\Framework\TestCase
{
    public function testAccessorsReturnTheConstructorValues(): void
    {
        $value = new Todo(1, 2, 'Title', 'Text', 'open', 3, '2026-12-31', false, '2026-01-10', 4, 'Allgemein');

        $this->assertSame([1, 2, 'Title', 'Text', 'open', 3, '2026-12-31', false, '2026-01-10', 4, 'Allgemein'], [$value->id(), $value->userId(), $value->title(), $value->text(), $value->status(), $value->priority(), $value->dueDate(), $value->archived(), $value->createdAt(), $value->categoryId(), $value->category()]);
    }
}
