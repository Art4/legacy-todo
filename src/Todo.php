<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Todo's read shape. Same class returned
 * by every read path of Todos; hydrated only by Todos, never written to
 * after construction. The public accessor count and 10-parameter constructor
 * are the shape itself — one accessor per field, one Todo per row — so the
 * PHPMD thresholds on both count as a Signal here, not a reason to split
 * the value object.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final class Todo
{
    /** @var int */
    private $id;

    /** @var int */
    private $userId;

    /** @var string */
    private $title;

    /** @var string */
    private $text;

    /** @var string */
    private $status;

    /** @var int */
    private $priority;

    /** @var string */
    private $dueDate;

    /** @var bool */
    private $archived;

    /** @var string */
    private $createdAt;

    /** @var string */
    private $category;

    public function __construct(
        int $id,
        int $userId,
        string $title,
        string $text,
        string $status,
        int $priority,
        string $dueDate,
        bool $archived,
        string $createdAt,
        string $category,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->text = $text;
        $this->status = $status;
        $this->priority = $priority;
        $this->dueDate = $dueDate;
        $this->archived = $archived;
        $this->createdAt = $createdAt;
        $this->category = $category;
    }

    /** @return int */
    public function id()
    {
        return $this->id;
    }

    /** @return int */
    public function userId()
    {
        return $this->userId;
    }

    /** @return string */
    public function title()
    {
        return $this->title;
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }

    /** @return string */
    public function status()
    {
        return $this->status;
    }

    /** @return int */
    public function priority()
    {
        return $this->priority;
    }

    /** @return string */
    public function dueDate()
    {
        return $this->dueDate;
    }

    /** @return bool */
    public function archived()
    {
        return $this->archived;
    }

    /** @return string */
    public function createdAt()
    {
        return $this->createdAt;
    }

    /** @return string */
    public function category()
    {
        return $this->category;
    }
}
