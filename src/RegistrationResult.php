<?php

namespace Art4\LegacyTodo;

/**
 * Typed result returned by Users::register(), replacing the old
 * bool-or-string return contract. One instance per registration attempt.
 */
final class RegistrationResult
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_INVALID_INPUT = 'invalid-input';
    public const STATUS_USERNAME_TAKEN = 'username-taken';
    public const STATUS_STORAGE_FAILURE = 'storage-failure';

    /** @var string */
    private $status;

    /**
     * @param string $status One of the STATUS_* constants
     */
    private function __construct(string $status)
    {
        $this->status = $status;
    }

    /** @return self */
    public static function registered(): self
    {
        return new self(self::STATUS_REGISTERED);
    }

    /** @return self */
    public static function invalidInput(): self
    {
        return new self(self::STATUS_INVALID_INPUT);
    }

    /** @return self */
    public static function usernameTaken(): self
    {
        return new self(self::STATUS_USERNAME_TAKEN);
    }

    /** @return self */
    public static function storageFailure(): self
    {
        return new self(self::STATUS_STORAGE_FAILURE);
    }

    /** @return string */
    public function status(): string
    {
        return $this->status;
    }

    public function isRegistered(): bool
    {
        return $this->status === self::STATUS_REGISTERED;
    }
}
