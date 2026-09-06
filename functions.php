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

function oldTodoFunc()
{
    return "dead";
}
