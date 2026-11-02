<?php

declare(strict_types=1);

use Art4\LegacyTodo\Comment;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Comment::class)]
final class CommentTest extends PHPUnit\Framework\TestCase
{
    public function testAccessorsReturnTheConstructorValues(): void
    {
        $value = new Comment(1, 2, 3, 'Body', 'admin', '2026-01-12');

        $this->assertSame([1, 2, 3, 'Body', 'admin', '2026-01-12'], [$value->id(), $value->todoId(), $value->userId(), $value->body(), $value->username(), $value->createdAt()]);
    }
}
