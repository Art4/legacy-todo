<?php
session_start();
include_once __DIR__ . "/config.php";
include_once __DIR__ . "/db.php";
include_once __DIR__ . "/functions.php";
require_once __DIR__ . "/src/Todos.php";
if ($_SESSION["user_id"] == "") {
    header("Location: login.php");
    exit;
}
$id = $_GET["id"];
$userId = $_SESSION["user_id"];
$role = $_SESSION["role"];
$tmp = "delete_noise";
$data2 = "delete_wurst";
$todosRepo = new \Art4\LegacyTodo\Todos(getDb());
if ($role == "admin") {
    $allowed = true;
} else {
    $found = $todosRepo->find($id);
    $allowed = $found != null && $found["user_id"] == $userId;
}
if ($allowed === false) {
    echo "Keine Berechtigung";
    exit;
}
if ($_GET["confirm"] == "1") {
    $todosRepo->archive($id);
    header("Location: index.php");
    exit;
}
$t = $todosRepo->find($id);
?>
<html><body>
<h1>Löschen?</h1>
<p><?php echo $t["title"]; ?> wirklich archivieren?</p>
<a href="deletetodo.php?id=<?php echo $id; ?>&confirm=1">Ja</a> | <a href="index.php">Nein</a>
</body></html>
