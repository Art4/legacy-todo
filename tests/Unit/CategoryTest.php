<?php

declare(strict_types=1);

use Art4\LegacyTodo\Category;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Category::class)]
final class CategoryTest extends PHPUnit\Framework\TestCase
{
    public function testAccessorsReturnTheConstructorValues(): void
    {
        $value = new Category(1, 'Allgemein', 2);

        $this->assertSame([1, 'Allgemein', 2], [$value->id(), $value->name(), $value->userId()]);
    }
}
