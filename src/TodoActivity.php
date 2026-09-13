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

    /** @return array<int, Comment> */
    public function commentsForTodo($todoId)
    {
        $stmt = $this->pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON users.id=comments.user_id WHERE comments.todo_id=?");
        $stmt->execute([(int) $todoId]);

        return array_map(fn(array $row) => $this->commentFromRow($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /** @return bool */
    public function addComment($todoId, $userId, $body)
    {
        $stmt = $this->pdo->prepare("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (?,?,?,?)");
        $stmt->execute([(int) $todoId, (int) $userId, $body, date("Y-m-d H:i:s")]);

        return true;
    }

    /** @return array<int, Assignment> */
    public function assignmentsForTodo($todoId)
    {
        $stmt = $this->pdo->prepare("SELECT assignments.*, users.username FROM assignments JOIN users ON users.id=assignments.user_id WHERE assignments.todo_id=?");
        $stmt->execute([(int) $todoId]);

        return array_map(fn(array $row) => $this->assignmentFromRow($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /** @return bool */
    public function assign($todoId, $userId, $assignedBy)
    {
        $stmt = $this->pdo->prepare("INSERT INTO assignments (todo_id,user_id,assigned_by) VALUES (?,?,?)");
        $stmt->execute([(int) $todoId, (int) $userId, (int) $assignedBy]);

        return true;
    }

    /** @return Comment|null */
    public function findComment($commentId)
    {
        $stmt = $this->pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON users.id=comments.user_id WHERE comments.id=?");
        $stmt->execute([(int) $commentId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $this->commentFromRow($row);
    }

    /** @return bool */
    public function removeComment($commentId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id=?");
        $stmt->execute([(int) $commentId]);

        return true;
    }

    /**
     * @param array<string, mixed> $row
     * @return Comment
     */
    private function commentFromRow(array $row)
    {
        return new Comment((int) $row["id"], (int) $row["todo_id"], (int) $row["user_id"], (string) $row["body"], (string) $row["username"], (string) $row["created_at"]);
    }

    /**
     * @param array<string, mixed> $row
     * @return Assignment
     */
    private function assignmentFromRow(array $row)
    {
        return new Assignment((int) $row["id"], (int) $row["todo_id"], (int) $row["user_id"], (int) $row["assigned_by"], (string) $row["username"]);
    }
}
