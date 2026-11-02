<?php

namespace Art4\LegacyTodo;

/**
 * Typed result returned by Users::register(), replacing the old
 * bool-or-string return contract. One instance per registration attempt.
 */
final readonly class RegistrationResult
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_INVALID_INPUT = 'invalid-input';
    public const STATUS_USERNAME_TAKEN = 'username-taken';
    public const STATUS_STORAGE_FAILURE = 'storage-failure';

    /**
     * @param string $status One of the STATUS_* constants
     */
    private function __construct(private string $status) {}

    public static function registered(): self
    {
        return new self(self::STATUS_REGISTERED);
    }

    public static function invalidInput(): self
    {
        return new self(self::STATUS_INVALID_INPUT);
    }

    public static function usernameTaken(): self
    {
        return new self(self::STATUS_USERNAME_TAKEN);
    }

    public static function storageFailure(): self
    {
        return new self(self::STATUS_STORAGE_FAILURE);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isRegistered(): bool
    {
        return $this->status === self::STATUS_REGISTERED;
    }
}
