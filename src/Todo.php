<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Todo's read shape. Same class returned
 * by every read path of Todos; hydrated only by Todos, never written to
 * after construction. The public accessor count and 11-parameter constructor
 * are the shape itself — one accessor per field, one Todo per row — so the
 * PHPMD thresholds on both count as a Signal here, not a reason to split
 * the value object.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final class Todo
{
    public function __construct(private int $id, private int $userId, private string $title, private string $text, private string $status, private int $priority, private string $dueDate, private bool $archived, private string $createdAt, private ?int $categoryId, private string $category)
    {
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

    /** @return int|null */
    public function categoryId()
    {
        return $this->categoryId;
    }

    /** @return string */
    public function category()
    {
        return $this->category;
    }
}
