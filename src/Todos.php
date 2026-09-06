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

    /** @return array<int, array<string, mixed>> */
    public function listFiltered($status, $prio, $due)
    {
        $sql = "SELECT * FROM todos WHERE archived=0";
        if ($status != "") {
            $sql .= " AND status='" . $status . "'";
        }
        if ($prio != "") {
            $sql .= " AND priority=" . $prio;
        }
        if ($due != "") {
            $sql .= " AND due_date<'" . $due . "'";
        }
        $sql .= " ORDER BY status ASC, due_date ASC";
        $rows = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            if ($row["category_id"] == "") {
                $cat = ["name" => ""];
            } else {
                $cat = $this->pdo->query("SELECT * FROM categories WHERE id=" . $row["category_id"])->fetch(\PDO::FETCH_ASSOC);
            }
            $row["cat"] = $cat["name"];
            $row["tags"] = $this->pdo->query("SELECT * FROM todo_tags WHERE todo_id=" . $row["id"])->fetchAll(\PDO::FETCH_ASSOC);
            $out[] = $row;
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    public function search($q)
    {
        $sql = "SELECT * FROM todos WHERE LOWER(title) LIKE LOWER('%" . $q . "%') AND archived=0 ORDER BY status ASC, due_date ASC";

        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function find($id)
    {
        $row = $this->pdo->query("SELECT * FROM todos WHERE id=" . $id)->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }

    /** @return bool */
    public function archive($id)
    {
        $this->pdo->exec("UPDATE todos SET archived=1 WHERE id=" . $id);

        return true;
    }

    /** @return bool */
    public function create($userId, $title, $text, $priority, $due)
    {
        if ($title == "") {
            return false;
        }
        $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at,data2) VALUES (" . $userId . ",'" . $title . "','" . $text . "','open'," . $priority . ",'" . $due . "',0,'" . date("Y-m-d") . "','wurst')";
        try {
            $this->pdo->exec($sql);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /** @return bool */
    public function update($id, $title, $text, $priority, $status)
    {
        if ($title == "") {
            return false;
        }
        $sql = "UPDATE todos SET title='" . $title . "', text='" . $text . "', priority='" . $priority . "', status='" . $status . "' WHERE id=" . $id;
        $this->pdo->exec($sql);

        return true;
    }
}
