<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding the session-derived identity of the logged-in
 * user. Returned by Auth::currentUser() only; constructed only by Auth. The
 * session carries exactly these three fields (user_id, username, role) — never
 * an email — so this is its own VO rather than a partial User.
 */
final class AuthenticatedUser
{
    public function __construct(private readonly int $userId, private readonly string $username, private readonly string $role) {}

    /** @return int */
    public function userId()
    {
        return $this->userId;
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
}
