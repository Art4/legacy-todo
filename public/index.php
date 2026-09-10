<?php
require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$auth = $app->auth();
$auth->requireLogin();
$userId = $auth->currentUser()["user_id"];
$dashboard = $app->dashboard();
$layout = $app->layout();
if (($_GET["export"] ?? "") == "csv") {
    $csv = $dashboard->exportCsv($userId);
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"todos.csv\"");
    echo $csv;
    exit;
}
$overview = $dashboard->overview($_GET);
echo $layout->header();
?>
<html><head><title><?php echo $app->siteName(); ?></title></head>
<body>
<h1>Dashboard</h1>
<p>Offene: <?php echo $overview["stats"]["open"]; ?> | Erledigte: <?php echo $overview["stats"]["done"]; ?> | Überfällig: <?php echo $overview["stats"]["overdue"]; ?></p>
<form method="get">
<input name="q" placeholder="Suche" value="<?php echo $layout->attr($_GET["q"] ?? ""); ?>">
<select name="status"><option value="">Status</option><option value="open">offen</option><option value="done">erledigt</option></select>
<select name="priority"><option value="">Prio</option><option value="1">Hoch</option><option value="2">Normal</option><option value="3">Niedrig</option></select>
<input name="due" type="date">
<input type="submit" value="Filtern">
</form>
<?php
$todos = $overview["todos"];
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
<?php echo $layout->footer(); ?>
</body></html>
