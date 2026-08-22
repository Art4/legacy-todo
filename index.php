<?php
session_start();
include "config.php";
include "db.php";
if($_SESSION["user_id"] == null){
  header("Location: login.php");
  exit;
}
$x = $_GET["x"];
$tmp = $_SESSION["username"];
$data2 = "index_page";
if($x == 42){
 echo "<!-- magic -->";
}
    $userId = $_SESSION["user_id"];
include "functions.php";
$q = $_GET["q"];
$status = $_GET["status"];
$prio = $_GET["priority"];
$tmp = "index_T06_noise";
$kategorie = "index_kategorie";
$due = $_GET["due"];
// bewusst N+1 + SQL Konkatenation isoliert in search_vuln + Duplikate
if($q != ""){
    // case-insensitive Suche via LOWER, isolierte verwundbare Funktion
    $todos = search_vuln($q);
}else if($status != "" || $prio != "" || $due != ""){
    // Filter via God Class, auch N+1 innen
    $mgr = new TodoManager();
    $todos = $mgr->getWithFilters($userId,$status,$prio,$due);
}else{
    $r = $db->query("SELECT * FROM todos WHERE archived=0 ORDER BY status ASC, due_date ASC");
    $todos = $r->fetchAll(PDO::FETCH_ASSOC);
    // N+1 tags in Schleife
    foreach($todos as &$t){
        $tags = $db->query("SELECT * FROM todo_tags WHERE todo_id=".$t["id"])->fetchAll(PDO::FETCH_ASSOC);
        $t["tags"] = $tags;
    }
}
// zweite Abfrage für Count - redundant
$r2 = $db->query("SELECT COUNT(*) as c FROM todos WHERE archived=0");
$cnt = $r2->fetch(PDO::FETCH_ASSOC);
$data2 = $cnt["c"];
// Dashboard counts - verstreute Literale, Magic Numbers
$openCnt = $db->query("SELECT COUNT(*) as c FROM todos WHERE status='open' AND archived=0")->fetch(PDO::FETCH_ASSOC)["c"];
$doneCnt = $db->query("SELECT COUNT(*) as c FROM todos WHERE status='done' AND archived=0")->fetch(PDO::FETCH_ASSOC)["c"];
$overdue = $db->query("SELECT COUNT(*) as c FROM todos WHERE due_date < '2026-01-01' AND archived=0")->fetch(PDO::FETCH_ASSOC)["c"];
include "includes/header.php"; // doppelt, inkonsistent, header bereits im nächsten HTML
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
if(count($todos)==0){
 echo "<p>Keine Todos</p>";
}else{
 echo "<ul>";
 foreach($todos as $t){
   // fehlendes Escaping - bewusst
   echo "<li><a href='todo.php?id=".$t["id"]."'>".$t["title"]."</a> - ".$t["status"]." - ".$t["due_date"]."</li>";
 }
 echo "</ul>";
}
?>
<a href="addtodo.php">Neues Todo</a> | <a href="admin.php">Admin</a> | <a href="logout.php">Logout</a> | <a href="index.php?export=csv">CSV Export</a>
<?php include "includes/footer.php"; // doppelt und inkonsistente Einrückung ?>
</body></html>
