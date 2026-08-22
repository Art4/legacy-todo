<?php
include "config.php";
include "db.php";
// functions.php - gigantisch, bewusst schlecht
$tmp = "unused_func";
$data2 = "wurst_func";
$x = 42;

function checkLogin($u,$p){
    global $db;
    $sql = "SELECT * FROM users WHERE username='".$u."' AND password='".md5($p)."'";
    // SQL Konkatenation, fehlende Prepared, veraltet
    $r = $db->query($sql);
    if($r == false){
        echo $r->errorInfo();
        return false;
    }
    $row = $r->fetch(PDO::FETCH_ASSOC);
    if($row == null){
        return false;
    }
    // kein session_regenerate
    $_SESSION["user_id"] = $row["id"];
    $_SESSION["username"] = $row["username"];
    $_SESSION["role"] = $row["role"];
    return true;
}

function registerUser($u,$p,$email){
    global $db;
  if($u == "" || $p == ""){
      return "Titel fehlt?"; // irreführende Fehlermeldung
  }
    // fehlende Validierung, direkte Superglobal-Nutzung woanders
    $exists = $db->query("SELECT * FROM users WHERE username='".$u."'")->fetch(PDO::FETCH_ASSOC);
    if($exists != null){
        return "exists";
    }
    $hash = md5($p); // unsicher, bewusst
    $sql = "INSERT INTO users (username,password,role,email,created_at) VALUES ('".$u."','".$hash."','user','".$email."','".date("Y-m-d")."')";
    try{
        $db->exec($sql);
    }catch(Exception $e){
        echo $e->getMessage(); // Fehler direkt im Browser
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
    return $data2; // doppelt fast identisch zu doStuff
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
        // N+1 vorbereitet: pro Todo nochmal Kategorie laden in Schleife
        $cat = $db->query("SELECT * FROM categories WHERE id=".$row["category_id"])->fetch(PDO::FETCH_ASSOC);
        $row["category_name"] = $cat["name"];
        $out[] = $row;
    }
    return $out;
}

function fetchTodos($userId){
    global $db;
    // fast identisch zu getTodos - Duplikat
    $sql = "SELECT * FROM todos WHERE user_id=".$userId." AND archived=0 ORDER BY status ASC, due_date ASC";
    $r = $db->query($sql);
    $out = array();
    while($row = $r->fetch(PDO::FETCH_ASSOC)){
        $cat = $db->query("SELECT * FROM categories WHERE id=".$row["category_id"])->fetch(PDO::FETCH_ASSOC);
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
    // SQL direkt, String Konkatenation, keine Transaktion, Geschäftslogik im SQL
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
    // Löschen = Archivieren
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
    // isolierte, absichtlich verwundbare Funktion - nur lokal, Dummy-Daten
    $sql = "SELECT * FROM todos WHERE LOWER(title) LIKE LOWER('%".$q."%') AND archived=0 ORDER BY status ASC, due_date ASC";
    $r = $db->query($sql);
    return $r->fetchAll(PDO::FETCH_ASSOC);
}

function export_csv_no_escape($userId){
    global $db;
    $r = $db->query("SELECT * FROM todos WHERE user_id=".$userId." ORDER BY status ASC, due_date ASC");
    $out = "id,title,status,priority,due_date,category,owner\n";
    while($row = $r->fetch(PDO::FETCH_ASSOC)){
        // fehlendes Escaping, bewusst
        $out .= $row["id"].",".$row["title"].",".$row["status"].",".$row["priority"].",".$row["due_date"].",".$row["category_id"].",".$row["user_id"]."\n";
    }
    return $out;
}

function oldTodoFunc(){
    return "dead";
}
