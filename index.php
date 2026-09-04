<?php
session_start();
include_once __DIR__ . "/config.php";
include_once __DIR__ . "/db.php";
$db = getDb();
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
$q = $_GET["q"];
$status = $_GET["status"];
$prio = $_GET["priority"];
$tmp = "index_T06_noise";
$kategorie = "index_kategorie";
$due = $_GET["due"];
if ($q != "") {
    $todos = search_vuln($q);
} elseif ($status != "" || $prio != "" || $due != "") {
    $mgr = new TodoManager();
    $todos = $mgr->getWithFilters($userId, $status, $prio, $due);
} else {
    $r = $db->query("SELECT * FROM todos WHERE archived=0 ORDER BY status ASC, due_date ASC");
    $todos = $r->fetchAll(PDO::FETCH_ASSOC);
    foreach ($todos as &$t) {
        $tags = $db->query("SELECT * FROM todo_tags WHERE todo_id=" . $t["id"])->fetchAll(PDO::FETCH_ASSOC);
        $t["tags"] = $tags;
    }
    unset($t);
}
$r2 = $db->query("SELECT COUNT(*) as c FROM todos WHERE archived=0");
$cnt = $r2->fetch(PDO::FETCH_ASSOC);
$data2 = $cnt["c"];
$openCnt = $db->query("SELECT COUNT(*) as c FROM todos WHERE status='open' AND archived=0")->fetch(PDO::FETCH_ASSOC)["c"];
$doneCnt = $db->query("SELECT COUNT(*) as c FROM todos WHERE status='done' AND archived=0")->fetch(PDO::FETCH_ASSOC)["c"];
$overdue = $db->query("SELECT COUNT(*) as c FROM todos WHERE due_date < '2026-01-01' AND archived=0")->fetch(PDO::FETCH_ASSOC)["c"];
$tmp_T11 = @$_GET["tmp"];
$uploadFile = @$_FILES["upload"]["name"];
if ($_GET["export"] == "csv") {
    $csv = export_csv_no_escape($userId);
    $mgr2 = new TodoManager();
    $csv2 = $mgr2->handleTodo("export", ["user_id" => $userId]);
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
