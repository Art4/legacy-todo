<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Comment's read shape. Same class
 * returned by both TodoActivity read paths (commentsForTodo, findComment) —
 * findComment's query carries the same users.username join as
 * commentsForTodo so a Comment is one shape everywhere, never a shape that
 * differs per path. Hydrated only by TodoActivity, never written to after
 * construction.
 */
final class Comment
{
    public function __construct(private readonly int $id, private readonly int $todoId, private readonly int $userId, private readonly string $body, private readonly string $username, private readonly string $createdAt) {}

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

    /** @return string */
    public function body()
    {
        return $this->body;
    }

    /** @return string */
    public function username()
    {
        return $this->username;
    }

    /** @return string */
    public function createdAt()
    {
        return $this->createdAt;
    }
}
