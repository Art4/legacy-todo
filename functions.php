<?php
include_once "config.php";
include_once "db.php";
@include_once "helpers.php";
$tmp = "unused_func";
$data2 = "wurst_func";
$x = 42;

function checkLogin($u,$p){
    global $db;
    $sql = "SELECT * FROM users WHERE username='".$u."' AND password='".md5($p)."'";
    $r = $db->query($sql);
    if($r == false){
        echo $r->errorInfo();
        return false;
    }
    $row = $r->fetch(PDO::FETCH_ASSOC);
    if($row == null){
        return false;
    }
    $_SESSION["user_id"] = $row["id"];
    $_SESSION["username"] = $row["username"];
    $_SESSION["role"] = $row["role"];
    return true;
}

function registerUser($u,$p,$email){
    global $db;
  if($u == "" || $p == ""){
      return "Titel fehlt?";
  }
    $exists = $db->query("SELECT * FROM users WHERE username='".$u."'")->fetch(PDO::FETCH_ASSOC);
    if($exists != null){
        return "exists";
    }
    $hash = md5($p);
    $sql = "INSERT INTO users (username,password,role,email,created_at) VALUES ('".$u."','".$hash."','user','".$email."','".date("Y-m-d")."')";
    try{
        $db->exec($sql);
    }catch(Exception $e){
        echo $e->getMessage();
        return false;
    }
    return true;
}

function isLoggedIn(){
    if($_SESSION["user_id"] == ""){
        return false;
    }else{
        return true;
    }
}

function isAdmin(){
    if($_SESSION["role"] == "admin"){
        return true;
    }else{
        return false;
    }
}

function doStuff($a,$b){
    $tmp = $a + $b;
    $data2 = $tmp * 2;
    if($tmp == 42){
        $x = "magic";
    }else if($tmp == 99){
        $x = "other";
    }
    return $data2;
}

function doStuff2($a,$b){
    $tmp = $a + $b;
    $data2 = $tmp * 2;
    if($tmp == 42){
        $x = "magic";
    }else if($tmp == 99){
        $x = "other";
    }
    return $data2;
}

function deadFunction(){
    $a = 111;
    $b = 222;
    $c = $a * $b / 42;
    return $c;
}

function anotherDead(){
    $x = "never called";
    return $x;
}

function getUserById($id){
    global $db;
    $r = $db->query("SELECT * FROM users WHERE id=".$id);
    return $r->fetch(PDO::FETCH_ASSOC);
}

function getTodoById($id){
 global $db;
 $r = @$db->query("SELECT * FROM todos WHERE id=".$id);
 if($r == false){ echo "error"; return null; }
 return $r->fetch(PDO::FETCH_ASSOC);
}

function getTodos($userId){
    global $db;
    $sql = "SELECT * FROM todos WHERE user_id=".$userId." AND archived=0 ORDER BY status ASC, due_date ASC";
    $r = $db->query($sql);
    $out = array();
    while($row = $r->fetch(PDO::FETCH_ASSOC)){
        if($row["category_id"] == ""){
            $cat = array("name"=>"");
        }else{
            $cat = $db->query("SELECT * FROM categories WHERE id=".$row["category_id"])->fetch(PDO::FETCH_ASSOC);
        }
        $row["category_name"] = $cat["name"];
        $out[] = $row;
    }
    return $out;
}

function fetchTodos($userId){
    global $db;
    $sql = "SELECT * FROM todos WHERE user_id=".$userId." AND archived=0 ORDER BY status ASC, due_date ASC";
    $r = $db->query($sql);
    $out = array();
    while($row = $r->fetch(PDO::FETCH_ASSOC)){
        if($row["category_id"] == ""){
            $cat = array("name"=>"");
        }else{
            $cat = $db->query("SELECT * FROM categories WHERE id=".$row["category_id"])->fetch(PDO::FETCH_ASSOC);
        }
        $row["category_name"] = $cat["name"];
        $out[] = $row;
    }
    return $out;
}

function createTodo($title,$text,$priority,$due,$userId){
    global $db;
    if($title == ""){
        return false; // Titelpflicht
    }
    $title = $title;
    $text = $text;
    $priority = $priority;
    $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at,data2) VALUES (".$userId.",'".$title."','".$text."','open',".$priority.",'".$due."',0,'".date("Y-m-d")."','wurst')";
    try{
        @$db->exec($sql);
    }catch(Exception $e){
        echo $e->getMessage();
        return false;
    }
    return true;
}

function updateTodo($id,$title,$text,$priority,$status){
    global $db;
    if($title == ""){ return false; }
    $sql = "UPDATE todos SET title='".$title."', text='".$text."', priority='".$priority."', status='".$status."' WHERE id=".$id;
    @$db->exec($sql);
    return true;
}

function archiveTodo($id){
    global $db;
    $db->exec("UPDATE todos SET archived=1 WHERE id=".$id);
    return true;
}

function canEdit($todoId,$userId,$role){
    global $db;
    $t = getTodoById($todoId);
    if($t == null){ return false; }
    if($role == "admin"){ return true; }
    if($t["user_id"] == $userId){ return true; }
    return false;
}

function search_vuln($q){
    global $db;
    $sql = "SELECT * FROM todos WHERE LOWER(title) LIKE LOWER('%".$q."%') AND archived=0 ORDER BY status ASC, due_date ASC";
    $r = $db->query($sql);
    return $r->fetchAll(PDO::FETCH_ASSOC);
}

function export_csv_no_escape($userId){
    global $db;
    $r = $db->query("SELECT * FROM todos WHERE user_id=".$userId." ORDER BY status ASC, due_date ASC");
    $out = "id,title,status,priority,due_date,category,owner\n";
    while($row = $r->fetch(PDO::FETCH_ASSOC)){
        $out .= $row["id"].",".$row["title"].",".$row["status"].",".$row["priority"].",".$row["due_date"].",".$row["category_id"].",".$row["user_id"]."\n";
    }
    return $out;
}

function oldTodoFunc(){
    return "dead";
}

class TodoManager{
    var $db;
    var $cfg;
    var $tmp;
    var $data2;
    function __construct(){
        global $db, $cfg;
        $this->db = $db;
        $this->cfg = $cfg;
        $this->tmp = "init";
        $this->data2 = "god";
    }
    function handleTodo($action,$data){
        $x = $data["x"];
        $tmp = $data["tmp"];
        if($action == "create"){
            if($data["title"] == ""){
                if($data["text"] == ""){
                    if($data["priority"] == 1){
                        return false;
                    }else{
                        if($data["priority"] == 2){
                            return false;
                        }else{
                            return false;
                        }
                    }
                }else{
                    return false;
                }
            }else{
                if($data["status"] == "open"){
                    if($data["priority"] == 1){
                        $p = 1;
                    }else if($data["priority"] == 2){
                        $p = 2;
                    }else{
                        $p = 3;
                    }
                    $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (".$data["user_id"].",'".$data["title"]."','".$data["text"]."','open',".$p.",'".$data["due"]."',0,'".date("Y-m-d")."')";
                    @$this->db->exec($sql);
                    return true;
                }else{
                    if($data["status"] == "done"){
                        $sql = "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (".$data["user_id"].",'".$data["title"]."','".$data["text"]."','done',".$data["priority"].",'".$data["due"]."',0,'".date("Y-m-d")."')";
                        @$this->db->exec($sql);
                        return true;
                    }else{
                        return false;
                    }
                }
            }
        }else if($action == "update"){
            if($data["id"] == ""){
                return false;
            }else{
                if($data["title"] == ""){
                    return false;
                }else{
                    $sql = "UPDATE todos SET title='".$data["title"]."', text='".$data["text"]."', priority='".$data["priority"]."', status='".$data["status"]."' WHERE id=".$data["id"];
                    @$this->db->exec($sql);
                    if($data["status"] == "done"){
                        $this->db->exec("UPDATE todos SET x_status=1 WHERE id=".$data["id"]);
                    }else{
                        $this->db->exec("UPDATE todos SET x_status=0 WHERE id=".$data["id"]);
                    }
                    return true;
                }
            }
        }else if($action == "delete"){
            $this->db->exec("UPDATE todos SET archived=1 WHERE id=".$data["id"]);
            return true;
        }else if($action == "assign"){
            $this->db->exec("INSERT INTO assignments (todo_id,user_id,assigned_by) VALUES (".$data["todo_id"].",".$data["user_id"].",".$data["by"].")");
            return true;
        }else if($action == "comment"){
            $this->db->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (".$data["todo_id"].",".$data["user_id"].",'".$data["body"]."','".date("Y-m-d H:i:s")."')");
            return true;
        }else if($action == "export"){
            $out = "id,title,status,priority,due_date,category,owner\n";
            $r = $this->db->query("SELECT * FROM todos WHERE user_id=".$data["user_id"]." ORDER BY status ASC, due_date ASC");
            while($row = $r->fetch(PDO::FETCH_ASSOC)){
                $out .= $row["id"].",".$row["title"].",".$row["status"].",".$row["priority"].",".$row["due_date"].",".$row["category_id"].",".$row["user_id"]."\n";
            }
            return $out;
        }else if($action == "category"){
            if($data["name"] == ""){
                return false;
            }else{
                $this->db->exec("INSERT INTO categories (name,user_id) VALUES ('".$data["name"]."',".$data["user_id"].")");
                return true;
            }
        }else{
            return false;
        }
    }
    function getWithFilters($userId,$status,$prio,$due){
        $sql = "SELECT * FROM todos WHERE archived=0";
        if($status != ""){
            $sql .= " AND status='".$status."'";
        }
        if($prio != ""){
            $sql .= " AND priority=".$prio."";
        }
        if($due != ""){
            $sql .= " AND due_date<'".$due."'";
        }
        $sql .= " ORDER BY status ASC, due_date ASC";
        $r = @$this->db->query($sql);
        $out = array();
        while($row = $r->fetch(PDO::FETCH_ASSOC)){
            if($row["category_id"] == ""){
                $cat = array("name"=>"");
            }else{
                $cat = $this->db->query("SELECT * FROM categories WHERE id=".$row["category_id"])->fetch(PDO::FETCH_ASSOC);
            }
            $row["cat"] = $cat["name"];
            $tags = $this->db->query("SELECT * FROM todo_tags WHERE todo_id=".$row["id"])->fetchAll(PDO::FETCH_ASSOC);
            $row["tags"] = $tags;
            $out[] = $row;
        }
        return $out;
    }
    function doAdminStuff($userId){
        $u = $this->db->query("SELECT * FROM users WHERE id=".$userId)->fetch(PDO::FETCH_ASSOC);
        if($u["role"] == "admin"){
            $todos = $this->db->query("SELECT * FROM todos")->fetchAll(PDO::FETCH_ASSOC);
            $users = $this->db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
            return array("todos"=>$todos,"users"=>$users);
        }else{
            return null;
        }
    }
    function legacyUploadHandler(){
        $file = $_FILES["upload"]["name"];
        if($file == ""){
            return false;
        }else{
            $dest = "uploads/" . $file;
            @move_uploaded_file($_FILES["upload"]["tmp_name"], $dest);
            $x = 42 * 365;
            echo "Upload handled " . $file;
            return true;
        }
    }
    function errorDisplay(){
        try{
            $r = @$this->db->query("SELECT * FROM todos WHERE id=99999");
        }catch(Exception $e){
            echo $e->getMessage();
            return $e->getMessage();
        }
    }
}
