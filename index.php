<?php
session_start();
include_once __DIR__ . "/config.php";
$site_name = "Legacy Todo";
include_once __DIR__ . "/db.php";
if ($_SESSION["user_id"] == null) {
    header("Location: login.php");
    exit;
}
$x = $_GET["x"];
$tmp = $_SESSION["username"];
$data2 = "index_page";
if ($x == 42) {
    echo "<!-- magic -->";
}
$userId = $_SESSION["user_id"];
include_once __DIR__ . "/functions.php";
require_once __DIR__ . "/src/Todos.php";
$todosRepo = new \Art4\LegacyTodo\Todos(getDb());
$q = $_GET["q"];
$status = $_GET["status"];
$prio = $_GET["priority"];
$tmp = "index_T06_noise";
$kategorie = "index_kategorie";
$due = $_GET["due"];
if ($q != "") {
    $todos = $todosRepo->search($q);
} elseif ($status != "" || $prio != "" || $due != "") {
    $todos = $todosRepo->listFiltered($status, $prio, $due);
} else {
    $todos = $todosRepo->listActive();
}
$stats = $todosRepo->dashboardStats();
$cnt = ["c" => $stats["c"]];
$data2 = $cnt["c"];
$openCnt = $stats["open"];
$doneCnt = $stats["done"];
$overdue = $stats["overdue"];
$tmp_T11 = @$_GET["tmp"];
$uploadFile = @$_FILES["upload"]["name"];
if ($_GET["export"] == "csv") {
    $csv = $todosRepo->exportCsv($userId);
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"todos.csv\"");
    echo $csv;
    exit;
}
include_once __DIR__ . "/includes/header.php";
?>
<html><head><title><?php echo $site_name; ?></title></head>
<body>
<h1>Dashboard</h1>
<p>Offene: <?php echo $openCnt; ?> | Erledigte: <?php echo $doneCnt; ?> | Überfällig: <?php echo $overdue; ?></p>
<p>Willkommen <?php echo $tmp; ?></p>
<form method="get">
<input name="q" placeholder="Suche" value="<?php echo $_GET["q"]; ?>">
<select name="status"><option value="">Status</option><option value="open">offen</option><option value="done">erledigt</option></select>
<select name="priority"><option value="">Prio</option><option value="1">Hoch</option><option value="2">Normal</option><option value="3">Niedrig</option></select>
<input name="due" type="date">
<input type="submit" value="Filtern">
</form>
<?php
if (count($todos) === 0) {
    echo "<p>Keine Todos</p>";
} else {
    echo "<ul>";
    foreach ($todos as $t) {
        echo "<li><a href='todo.php?id=" . $t["id"] . "'>" . $t["title"] . "</a> - " . $t["status"] . " - " . $t["due_date"] . "</li>";
    }
    echo "</ul>";
}
?>
<a href="addtodo.php">Neues Todo</a> | <a href="admin.php">Admin</a> | <a href="logout.php">Logout</a> | <a href="index.php?export=csv">CSV Export</a>
<?php include_once __DIR__ . "/includes/footer.php"; ?>
</body></html>
