<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Assignment's read shape. Same class
 * returned by TodoActivity::assignmentsForTodo(), hydrated only by
 * TodoActivity, never written to after construction.
 */
final class Assignment
{
    public function __construct(private readonly int $id, private readonly int $todoId, private readonly int $userId, private readonly int $assignedBy, private readonly string $username) {}

    /** @return int */
    public function id()
    {
        return $this->id;
    }

    /** @return int */
    public function todoId()
    {
        return $this->todoId;
    }

    /** @return int */
    public function userId()
    {
        return $this->userId;
    }

    /** @return int */
    public function assignedBy()
    {
        return $this->assignedBy;
    }

    /** @return string */
    public function username()
    {
        return $this->username;
    }
}
