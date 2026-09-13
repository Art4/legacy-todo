<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Assignment's read shape. Same class
 * returned by TodoActivity::assignmentsForTodo(), hydrated only by
 * TodoActivity, never written to after construction.
 */
final class Assignment
{
    /** @var int */
    private $id;

    /** @var int */
    private $todoId;

    /** @var int */
    private $userId;

    /** @var int */
    private $assignedBy;

    /** @var string */
    private $username;

    public function __construct(int $id, int $todoId, int $userId, int $assignedBy, string $username)
    {
        $this->id = $id;
        $this->todoId = $todoId;
        $this->userId = $userId;
        $this->assignedBy = $assignedBy;
        $this->username = $username;
    }

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
