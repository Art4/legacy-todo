<?php

declare(strict_types=1);

use Art4\LegacyTodo\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(User::class)]
final class UserTest extends PHPUnit\Framework\TestCase
{
    public function testAccessorsReturnTheConstructorValues(): void
    {
        $value = new User(1, 'admin', 'admin', 'admin@example.com');

        $this->assertSame([1, 'admin', 'admin', 'admin@example.com'], [$value->id(), $value->username(), $value->role(), $value->email()]);
    }
}
