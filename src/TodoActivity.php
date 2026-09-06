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

    /** @return bool */
    public function addComment($todoId, $userId, $body)
    {
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (" . $todoId . "," . $userId . ",'" . $body . "','" . date("Y-m-d H:i:s") . "')");

        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public function assignmentsForTodo($todoId)
    {
        return $this->pdo->query("SELECT assignments.*, users.username FROM assignments JOIN users ON users.id=assignments.user_id WHERE assignments.todo_id=" . $todoId)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
