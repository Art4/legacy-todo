<?php

include_once __DIR__ . "/config.php";
include_once __DIR__ . "/db.php";
@include_once __DIR__ . "/src/Helpers.php";
$tmp = "unused_func";
$data2 = "wurst_func";
$x = 42;

function checkLogin($u, $p)
{
    global $db;
    $sql = "SELECT * FROM users WHERE username='" . $u . "' AND password='" . md5($p) . "'";
    $r = $db->query($sql);
    if ($r == false) {
        echo $db->errorInfo()[2];
        return false;
    }
    $row = $r->fetch(PDO::FETCH_ASSOC);
    if ($row == null) {
        return false;
    }
    $_SESSION["user_id"] = $row["id"];
    $_SESSION["username"] = $row["username"];
    $_SESSION["role"] = $row["role"];
    return true;
}

function registerUser($u, $p, $email)
{
    global $db;
    if ($u == "" || $p == "") {
        return "Titel fehlt?";
    }
    $exists = $db->query("SELECT * FROM users WHERE username='" . $u . "'")->fetch(PDO::FETCH_ASSOC);
    if ($exists != null) {
        return "exists";
    }
    $hash = md5($p);
    $sql = "INSERT INTO users (username,password,role,email,created_at) VALUES ('" . $u . "','" . $hash . "','user','" . $email . "','" . date("Y-m-d") . "')";
    try {
        $db->exec($sql);
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
    return true;
}

function isLoggedIn()
{
    return $_SESSION["user_id"] != "";
}

function isAdmin()
{
    return $_SESSION["role"] == "admin";
}

function doStuff($a, $b)
{
    $tmp = $a + $b;
    $data2 = $tmp * 2;
    if ($tmp == 42) {
        $x = "magic";
    } elseif ($tmp == 99) {
        $x = "other";
    }
    return $data2;
}

function doStuff2($a, $b)
{
    $tmp = $a + $b;
    $data2 = $tmp * 2;
    if ($tmp == 42) {
        $x = "magic";
    } elseif ($tmp == 99) {
        $x = "other";
    }
    return $data2;
}

function deadFunction()
{
    $a = 111;
    $b = 222;
    return $a * $b / 42;
}

function anotherDead()
{
    return "never called";
}

function getUserById($id)
{
    global $db;
    $r = $db->query("SELECT * FROM users WHERE id=" . $id);
    return $r->fetch(PDO::FETCH_ASSOC);
}

function export_csv_no_escape($userId)
{
    global $db;
    $r = $db->query("SELECT * FROM todos WHERE user_id=" . $userId . " ORDER BY status ASC, due_date ASC");
    $out = "id,title,status,priority,due_date,category,owner\n";
    while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
        $out .= $row["id"] . "," . $row["title"] . "," . $row["status"] . "," . $row["priority"] . "," . $row["due_date"] . "," . $row["category_id"] . "," . $row["user_id"] . "\n";
    }
    return $out;
}

function oldTodoFunc()
{
    return "dead";
}

class TodoManager
{
    public $db;
    public $cfg;
    public $tmp;
    public $data2;
    public function __construct()
    {
        global $db, $cfg;
        $this->db = $db;
        $this->cfg = $cfg;
        $this->tmp = "init";
        $this->data2 = "god";
    }
    public function handleTodo($action, $data)
    {
        if ($action == "create") {
            if ($data["title"] == "") {
                return false;
            }
            if ($data["status"] == "open") {
                if ($data["priority"] == 1) {
                    $p = 1;
                } elseif ($data["priority"] == 2) {
                    $p = 2;
                } else {
                    $p = 3;
                }
                $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (" . $data["user_id"] . ",'" . $data["title"] . "','" . $data["text"] . "','open'," . $p . ",'" . $data["due"] . "',0,'" . date("Y-m-d") . "')";
                @$this->db->exec($sql);
                return true;
            }
            if ($data["status"] == "done") {
                $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (" . $data["user_id"] . ",'" . $data["title"] . "','" . $data["text"] . "','done'," . $data["priority"] . ",'" . $data["due"] . "',0,'" . date("Y-m-d") . "')";
                @$this->db->exec($sql);
                return true;
            }
            return false;
        }
        if ($action == "update") {
            if ($data["id"] == "") {
                return false;
            }
            if ($data["title"] == "") {
                return false;
            }
            $sql = "UPDATE todos SET title='" . $data["title"] . "', text='" . $data["text"] . "', priority='" . $data["priority"] . "', status='" . $data["status"] . "' WHERE id=" . $data["id"];
            @$this->db->exec($sql);
            if ($data["status"] == "done") {
                $this->db->exec("UPDATE todos SET x_status=1 WHERE id=" . $data["id"]);
            } else {
                $this->db->exec("UPDATE todos SET x_status=0 WHERE id=" . $data["id"]);
            }
            return true;
        }
        if ($action == "delete") {
            $this->db->exec("UPDATE todos SET archived=1 WHERE id=" . $data["id"]);
            return true;
        }
        if ($action == "assign") {
            $this->db->exec("INSERT INTO assignments (todo_id,user_id,assigned_by) VALUES (" . $data["todo_id"] . "," . $data["user_id"] . "," . $data["by"] . ")");
            return true;
        }
        if ($action == "comment") {
            $this->db->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (" . $data["todo_id"] . "," . $data["user_id"] . ",'" . $data["body"] . "','" . date("Y-m-d H:i:s") . "')");
            return true;
        }
        if ($action == "export") {
            $out = "id,title,status,priority,due_date,category,owner\n";
            $r = $this->db->query("SELECT * FROM todos WHERE user_id=" . $data["user_id"] . " ORDER BY status ASC, due_date ASC");
            while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
                $out .= $row["id"] . "," . $row["title"] . "," . $row["status"] . "," . $row["priority"] . "," . $row["due_date"] . "," . $row["category_id"] . "," . $row["user_id"] . "\n";
            }
            return $out;
        }
        if ($action == "category") {
            if ($data["name"] == "") {
                return false;
            }
            $this->db->exec("INSERT INTO categories (name,user_id) VALUES ('" . $data["name"] . "'," . $data["user_id"] . ")");
            return true;
        }
        return false;
    }
    public function getWithFilters($userId, $status, $prio, $due)
    {
        $sql = "SELECT * FROM todos WHERE archived=0";
        if ($status != "") {
            $sql .= " AND status='" . $status . "'";
        }
        if ($prio != "") {
            $sql .= " AND priority=" . $prio . "";
        }
        if ($due != "") {
            $sql .= " AND due_date<'" . $due . "'";
        }
        $sql .= " ORDER BY status ASC, due_date ASC";
        $r = @$this->db->query($sql);
        $out = [];
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            if ($row["category_id"] == "") {
                $cat = ["name" => ""];
            } else {
                $cat = $this->db->query("SELECT * FROM categories WHERE id=" . $row["category_id"])->fetch(PDO::FETCH_ASSOC);
            }
            $row["cat"] = $cat["name"];
            $tags = $this->db->query("SELECT * FROM todo_tags WHERE todo_id=" . $row["id"])->fetchAll(PDO::FETCH_ASSOC);
            $row["tags"] = $tags;
            $out[] = $row;
        }
        return $out;
    }
    public function doAdminStuff($userId)
    {
        $u = $this->db->query("SELECT * FROM users WHERE id=" . $userId)->fetch(PDO::FETCH_ASSOC);
        if ($u["role"] == "admin") {
            $todos = $this->db->query("SELECT * FROM todos")->fetchAll(PDO::FETCH_ASSOC);
            $users = $this->db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
            return ["todos" => $todos,"users" => $users];
        }
        return null;
    }
    public function legacyUploadHandler()
    {
        $file = $_FILES["upload"]["name"];
        if ($file == "") {
            return false;
        }
        $dest = "uploads/" . $file;
        @move_uploaded_file($_FILES["upload"]["tmp_name"], $dest);
        echo "Upload handled " . $file;
        return true;
    }
    public function errorDisplay()
    {
        try {
            $r = @$this->db->query("SELECT * FROM todos WHERE id=99999");
        } catch (Exception $e) {
            echo $e->getMessage();
            return $e->getMessage();
        }
        return null;
    }
}
