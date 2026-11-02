<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one User's read shape. Same class returned
 * by every read path of Users; hydrated only by Users, never written to
 * after construction. The password hash never surfaces — authenticate() and
 * the rehash logic stay inside Users, the VO carries the auth-relevant
 * identity fields only.
 */
final class User
{
    public function __construct(private int $id, private string $username, private string $role, private string $email) {}

    /** @return int */
    public function id()
    {
        return $this->id;
    }

    /** @return string */
    public function username()
    {
        return $this->username;
    }

    /** @return string */
    public function role()
    {
        return $this->role;
    }

    /** @return string */
    public function email()
    {
        return $this->email;
    }
}
