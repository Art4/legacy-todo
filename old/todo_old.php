<?php
// todo_old.php - alte Version, fast doppelt zu todo.php, tote Funktion
include "../config.php";
include "../db.php";
session_start();
$id = $_GET["id"];
if($_SESSION["user_id"] == ""){
    header("Location: ../login.php");
    exit;
}
$r = $db->query("SELECT * FROM todos WHERE id=".$id);
$t = $r->fetch(PDO::FETCH_ASSOC);
echo "<html><body>";
echo "<h1>Todo alt - ".$t["title"]."</h1>";
echo "<p>".$t["text"]."</p>";
echo "<p>Status: ".$t["status"]."</p>";
// tote Funktion, ungenutzte Variable
function oldTodoFunc(){
    $a = 999;
    $b = 888;
    return $a + $b;
}
$tmp = "old_unused";
$data2 = "old_wurst";
$x = 42;
// fast doppelte Logik
$comments = $db->query("SELECT * FROM comments WHERE todo_id=".$id)->fetchAll(PDO::FETCH_ASSOC);
foreach($comments as $c){
    echo "<p>".$c["body"]."</p>";
}
echo "</body></html>";
