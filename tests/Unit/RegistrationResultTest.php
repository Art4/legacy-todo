<?php

declare(strict_types=1);

use Art4\LegacyTodo\RegistrationResult;

final class RegistrationResultTest extends PHPUnit\Framework\TestCase
{
    public function testRegisteredResultHasCorrectStatus(): void
    {
        $result = RegistrationResult::registered();

        $this->assertSame('registered', $result->status());
        $this->assertTrue($result->isRegistered());
    }

    public function testInvalidInputResultHasCorrectStatus(): void
    {
        $result = RegistrationResult::invalidInput();

        $this->assertSame('invalid-input', $result->status());
        $this->assertFalse($result->isRegistered());
    }

    public function testUsernameTakenResultHasCorrectStatus(): void
    {
        $result = RegistrationResult::usernameTaken();

        $this->assertSame('username-taken', $result->status());
        $this->assertFalse($result->isRegistered());
    }

    public function testStorageFailureResultHasCorrectStatus(): void
    {
        $result = RegistrationResult::storageFailure();

        $this->assertSame('storage-failure', $result->status());
        $this->assertFalse($result->isRegistered());
    }

    public function testFactoryMethodsReturnDistinctInstances(): void
    {
        $a = RegistrationResult::registered();
        $b = RegistrationResult::registered();

        $this->assertNotSame($a, $b);
    }
}
