<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Tag's read shape. Same class returned
 * by Taxonomy::listTags(), hydrated only by Taxonomy, never written to
 * after construction.
 */
final class Tag
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
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
}
