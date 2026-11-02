<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Category's read shape. Same class
 * returned by Taxonomy::listCategories(), hydrated only by Taxonomy, never
 * written to after construction.
 */
final readonly class Category
{
    public function __construct(private int $id, private string $name, private int $userId) {}

    /** @return int */
    public function id()
    {
        return $this->id;
    }

    /** @return string */
    public function name()
    {
        return $this->name;
    }

    /** @return int */
    public function userId()
    {
        return $this->userId;
    }
}
