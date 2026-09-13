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
    /** @var int */
    private $id;

    /** @var int */
    private $todoId;

    /** @var int */
    private $userId;

    /** @var string */
    private $body;

    /** @var string */
    private $username;

    /** @var string */
    private $createdAt;

    public function __construct(int $id, int $todoId, int $userId, string $body, string $username, string $createdAt)
    {
        $this->id = $id;
        $this->todoId = $todoId;
        $this->userId = $userId;
        $this->body = $body;
        $this->username = $username;
        $this->createdAt = $createdAt;
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
