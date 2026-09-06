<?php

namespace Art4\LegacyTodo;

class TodoActivity
{
    /** @var \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
