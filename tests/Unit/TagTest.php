<?php

declare(strict_types=1);

use Art4\LegacyTodo\Tag;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tag::class)]
final class TagTest extends PHPUnit\Framework\TestCase
{
    public function testAccessorsReturnTheConstructorValues(): void
    {
        $value = new Tag(1, 'wichtig');

        $this->assertSame([1, 'wichtig'], [$value->id(), $value->name()]);
    }
}
