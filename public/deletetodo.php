<?php
require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$auth = $app->auth();
$auth->requireLogin();
$id = $_GET["id"];
$tmp = "delete_noise";
$data2 = "delete_wurst";
$todosRepo = $app->todos();
if (!$auth->canManage($id)) {
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
