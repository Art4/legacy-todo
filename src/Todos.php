<?php

namespace Art4\LegacyTodo;

class Todos
{
    /** @var \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive()
    {
        $rows = $this->pdo->query("SELECT * FROM todos WHERE archived=0 ORDER BY status ASC, due_date ASC")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row["tags"] = $this->pdo->query("SELECT * FROM todo_tags WHERE todo_id=" . $row["id"])->fetchAll(\PDO::FETCH_ASSOC);
        }
        unset($row);

        return $rows;
    }
}
