<?php

namespace Art4\LegacyTodo;

class Todos
{
    /** @var \PDO */
    private $pdo;

    /** @var Taxonomy|null */
    private $taxonomy;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function taxonomy(): Taxonomy
    {
        if ($this->taxonomy === null) {
            $this->taxonomy = new Taxonomy($this->pdo);
        }

        return $this->taxonomy;
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive()
    {
        $rows = $this->pdo->query("SELECT * FROM todos WHERE archived=0 ORDER BY status ASC, due_date ASC")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->decorate($rows);
    }

    /** @return array<int, array<string, mixed>> */
    public function listFiltered($status, $prio, $due)
    {
        $sql = "SELECT * FROM todos WHERE archived=0";
        $params = [];
        if ($status !== "") {
            $sql .= " AND status=?";
            $params[] = $status;
        }
        if ($prio !== "") {
            $sql .= " AND priority=?";
            $params[] = (int) $prio;
        }
        if ($due !== "") {
            $sql .= " AND due_date<?";
            $params[] = $due;
        }
        $sql .= " ORDER BY status ASC, due_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->decorate($rows);
    }

    /** @param array<int, array<string, mixed>> $rows
     *  @return array<int, array<string, mixed>>
     */
    private function decorate($rows)
    {
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row["id"];
        }
        if ($ids === []) {
            return $rows;
        }
        $catNames = $this->taxonomy()->categoryNamesForTodos($ids);
        $tagsMap = $this->taxonomy()->tagsForTodos($ids);
        foreach ($rows as &$row) {
            $row["cat"] = $catNames[(int) $row["id"]] ?? "";
            $row["tags"] = $tagsMap[(int) $row["id"]] ?? [];
        }
        unset($row);

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function search($q)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM todos WHERE LOWER(title) LIKE LOWER(?) AND archived=0 ORDER BY status ASC, due_date ASC");
        $stmt->execute(["%" . $q . "%"]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM todos WHERE id=?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }

    /** @return bool */
    public function archive($id)
    {
        $stmt = $this->pdo->prepare("UPDATE todos SET archived=1 WHERE id=?");
        $stmt->execute([(int) $id]);

        return true;
    }

    /** @return bool */
    public function create($userId, $title, $text, $priority, $due)
    {
        if ($title == "") {
            return false;
        }
        $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at,data2) VALUES (?,?,?,'open',?,?,0,?,?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(int) $userId, $title, $text, (int) $priority, $due, date("Y-m-d"), "wurst"]);
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
        $sql = "UPDATE todos SET title=?, text=?, priority=?, status=? WHERE id=?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$title, $text, $priority, $status, (int) $id]);

        return true;
    }

    /** @return string */
    public function exportCsv($userId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM todos WHERE user_id=? ORDER BY status ASC, due_date ASC");
        $stmt->execute([(int) $userId]);
        $out = "id,title,status,priority,due_date,category,owner\n";
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $out .= $row["id"] . "," . $row["title"] . "," . $row["status"] . "," . $row["priority"] . "," . $row["due_date"] . "," . $row["category_id"] . "," . $row["user_id"] . "\n";
        }

        return $out;
    }

    /** @return array<string, int> */
    public function dashboardStats()
    {
        $cnt = $this->pdo->query("SELECT COUNT(*) as c FROM todos WHERE archived=0")->fetch(\PDO::FETCH_ASSOC);
        $open = $this->pdo->query("SELECT COUNT(*) as c FROM todos WHERE status='open' AND archived=0")->fetch(\PDO::FETCH_ASSOC);
        $done = $this->pdo->query("SELECT COUNT(*) as c FROM todos WHERE status='done' AND archived=0")->fetch(\PDO::FETCH_ASSOC);
        $overdue = $this->pdo->query("SELECT COUNT(*) as c FROM todos WHERE due_date < '2026-01-01' AND archived=0")->fetch(\PDO::FETCH_ASSOC);

        return ["c" => $cnt["c"], "open" => $open["c"], "done" => $done["c"], "overdue" => $overdue["c"]];
    }
}
