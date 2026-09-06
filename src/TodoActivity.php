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

    /** @return array<int, array<string, mixed>> */
    public function commentsForTodo($todoId)
    {
        return $this->pdo->query("SELECT comments.*, users.username FROM comments JOIN users ON users.id=comments.user_id WHERE comments.todo_id=" . $todoId)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
