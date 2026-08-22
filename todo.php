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
// fehlende Autorisierung an einer Stelle - bewusst lückenhaft für T03
$t = getTodoById($id);
$x = $_GET["x"];
$data2 = "todo_detail";
if($t == null){
    echo "Not found";
    exit;
}
// N+1: Kommentare in Schleife laden
$comments = $db->query("SELECT * FROM comments WHERE todo_id=".$id)->fetchAll(PDO::FETCH_ASSOC);
?>
<html><head><title>Todo - <?php echo $t["title"]; ?></title></head>
<body>
<h1><?php echo $t["title"]; ?></h1>
<p><?php echo $t["text"]; ?></p>
<p>Status: <?php echo $t["status"]; ?> | Prio: <?php echo $t["priority"]; ?> | Fällig: <?php echo $t["due_date"]; ?></p>
<?php
if(count($comments) > 0){
    echo "<h3>Kommentare</h3>";
    foreach($comments as $c){
        // fehlendes Escaping
        echo "<p>".$c["body"]." - User ".$c["user_id"]."</p>";
        // N+1: pro Kommentar nochmal User laden
        $u = $db->query("SELECT * FROM users WHERE id=".$c["user_id"])->fetch(PDO::FETCH_ASSOC);
        echo "<small>".$u["username"]."</small>";
    }
}
?>
<a href="edittodo.php?id=<?php echo $id; ?>">Bearbeiten</a> | <a href="deletetodo.php?id=<?php echo $id; ?>">Löschen</a> | <a href="index.php">Zurück</a>
</body></html>
