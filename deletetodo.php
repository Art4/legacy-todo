<?php
session_start();
include "config.php";
include "db.php";
include "functions.php";
if($_SESSION["user_id"] == ""){
    header("Location: login.php");
    exit;
}
$id = $_GET["id"];
$userId = $_SESSION["user_id"];
$role = $_SESSION["role"];
$tmp = "delete_noise";
$data2 = "delete_wurst";
// lückenhaft: prüft nicht überall, aber hier schon
if(canEdit($id,$userId,$role) == false){
    echo "Keine Berechtigung";
    exit;
}
if($_GET["confirm"] == "1"){
    // Archivieren statt Löschen
    archiveTodo($id);
    header("Location: index.php");
    exit;
}
$t = getTodoById($id);
?>
<html><body>
<h1>Löschen?</h1>
<p><?php echo $t["title"]; ?> wirklich archivieren?</p>
<a href="deletetodo.php?id=<?php echo $id; ?>&confirm=1">Ja</a> | <a href="index.php">Nein</a>
</body></html>
