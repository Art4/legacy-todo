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
