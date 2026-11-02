<?php

namespace Art4\LegacyTodo;

/**
 * Immutable value object holding one Tag's read shape. Same class returned
 * by Taxonomy::listTags(), hydrated only by Taxonomy, never written to
 * after construction.
 */
final readonly class Tag
{
    public function __construct(private int $id, private string $name) {}

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
