<?php
/**
 * TodoRepository — the single place that owns todo persistence
 * (create / update / archive / find-by-id).
 *
 * Extracted from functions.php's free functions and TodoManager::handleTodo()'s
 * inlined "create"/"update"/"delete" branches, which had already drifted from
 * each other (see .scratch/refactor-issues/03-structural-todo-persistence.md).
 *
 * Behavior-preserving only (ADR-0004): the SQL below is unchanged from the
 * pre-existing free functions in functions.php, concatenation and all — this
 * refactor is about locality/duplication, not the pre-existing SQL-injection
 * vulnerability (out of scope for this candidate; the app's README already
 * documents these vulnerabilities as intentional test data).
 */
class TodoRepository
{
    private $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    function create($title, $text, $priority, $due, $userId)
    {
        if ($title == "") {
            return false; // Titelpflicht
        }
        $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at,data2) VALUES ("
            . $userId . ",'" . $title . "','" . $text . "','open'," . $priority . ",'" . $due . "',0,'" . date("Y-m-d") . "','wurst')";
        try {
            @$this->db->exec($sql);
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return true;
    }

    function update($id, $title, $text, $priority, $status)
    {
        if ($title == "") {
            return false;
        }
        $sql = "UPDATE todos SET title='" . $title . "', text='" . $text . "', priority='" . $priority . "', status='" . $status . "' WHERE id=" . $id;
        @$this->db->exec($sql);
        return true;
    }

    function archive($id)
    {
        $this->db->exec("UPDATE todos SET archived=1 WHERE id=" . $id);
        return true;
    }

    function findById($id)
    {
        $r = @$this->db->query("SELECT * FROM todos WHERE id=" . $id);
        if ($r == false) {
            echo "error";
            return null;
        }
        return $r->fetch(PDO::FETCH_ASSOC);
    }
}
