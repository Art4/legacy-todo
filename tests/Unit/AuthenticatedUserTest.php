<?php

declare(strict_types=1);

use Art4\LegacyTodo\AuthenticatedUser;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AuthenticatedUser::class)]
final class AuthenticatedUserTest extends PHPUnit\Framework\TestCase
{
    public function testAccessorsReturnTheConstructorValues(): void
    {
        $value = new AuthenticatedUser(1, 'admin', 'admin');

        $this->assertSame([1, 'admin', 'admin'], [$value->userId(), $value->username(), $value->role()]);
    }
}
