<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Category's read shape. Same class
 * returned by Taxonomy::listCategories(), hydrated only by Taxonomy, never
 * written to after construction.
 */
final class Category
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var int */
    private $userId;

    public function __construct(int $id, string $name, int $userId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->userId = $userId;
    }

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
