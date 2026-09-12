<?php

namespace Art4\LegacyTodo;

class Todos
{
    /** @var \PDO */
    private $pdo;

    /** @var Taxonomy */
    private $taxonomy;

    public function __construct(\PDO $pdo, Taxonomy $taxonomy)
    {
        $this->pdo = $pdo;
        $this->taxonomy = $taxonomy;
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
        $catNames = $this->taxonomy->categoryNamesForTodos($ids);
        $tagsMap = $this->taxonomy->tagsForTodos($ids);
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

    /** @return Todo|null */
    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM todos WHERE id=?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        $catNames = $this->taxonomy->categoryNamesForTodos([(int) $row["id"]]);

        return $this->todoFromRow($row, $catNames[(int) $row["id"]] ?? "");
    }

    /** @param array<string, mixed> $row
     *  @return Todo
     */
    private function todoFromRow(array $row, string $category)
    {
        return new Todo(
            (int) $row["id"],
            (int) $row["user_id"],
            (string) $row["title"],
            (string) $row["text"],
            (string) $row["status"],
            (int) $row["priority"],
            (string) $row["due_date"],
            (bool) $row["archived"],
            (string) $row["created_at"],
            $category,
        );
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
            $out .= $this->csvField($row["id"]) . "," . $this->csvField($row["title"]) . "," . $this->csvField($row["status"]) . "," . $this->csvField($row["priority"]) . "," . $this->csvField($row["due_date"]) . "," . $this->csvField($row["category_id"]) . "," . $this->csvField($row["user_id"]) . "\n";
        }

        return $out;
    }

    private function csvField($value): string
    {
        $value = (string) $value;
        if (strpbrk($value, ",\"\r\n") === false) {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

    /** @return array<string, int> */
    public function dashboardStats(string $asOfDate)
    {
        $cnt = $this->pdo->query("SELECT COUNT(*) as c FROM todos WHERE archived=0")->fetch(\PDO::FETCH_ASSOC);
        $open = $this->pdo->query("SELECT COUNT(*) as c FROM todos WHERE status='open' AND archived=0")->fetch(\PDO::FETCH_ASSOC);
        $done = $this->pdo->query("SELECT COUNT(*) as c FROM todos WHERE status='done' AND archived=0")->fetch(\PDO::FETCH_ASSOC);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as c FROM todos WHERE due_date < ? AND archived=0");
        $stmt->execute([$asOfDate]);
        $overdue = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ["c" => $cnt["c"], "open" => $open["c"], "done" => $done["c"], "overdue" => $overdue["c"]];
    }
}
