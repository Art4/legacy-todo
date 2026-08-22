<?php
session_start();
include_once "config.php";
include_once "db.php";
include_once "functions.php";
if($_SESSION["user_id"] == ""){
    header("Location: login.php");
    exit;
}
$id = $_GET["id"];
$t = getTodoById($id);
$x = $_GET["x"];
$data2 = "todo_detail";
$cat = $t["category_id"];
$kategorie = "kategorie";
if($t == null){
    echo "Not found";
    exit;
}
$next = $_GET["next"];
if($_POST["add_comment"]){
    $body = $_POST["body"];
    $uid = $_SESSION["user_id"];
    $db->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (".$id.",".$uid.",'".$body."','".date("Y-m-d H:i:s")."')");
    header("Location: todo.php?id=".$id);
    exit;
}
if($_POST["assign"]){
    $assignee = $_POST["assignee"];
    $db->exec("INSERT INTO assignments (todo_id,user_id,assigned_by) VALUES (".$id.",".$assignee.",".$_SESSION["user_id"].")");
    if($next != ""){
        header("Location: ".$next);
        exit;
    }
}
if($_GET["del_comment"]){
    $cid = $_GET["del_comment"];
    $db->exec("DELETE FROM comments WHERE id=".$cid);
}
$comments = $db->query("SELECT * FROM comments WHERE todo_id=".$id)->fetchAll(PDO::FETCH_ASSOC);
$assigns = $db->query("SELECT * FROM assignments WHERE todo_id=".$id)->fetchAll(PDO::FETCH_ASSOC);
$tmp_T11 = @$_FILES["upload"]["name"];
$magic = 42;
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
        echo "<p>".$c["body"]." - User ".$c["user_id"]." <a href='todo.php?id=".$id."&del_comment=".$c["id"]."'>löschen</a></p>";
        $u = $db->query("SELECT * FROM users WHERE id=".$c["user_id"])->fetch(PDO::FETCH_ASSOC);
        echo "<small>".$u["username"]."</small>";
    }
}
if(count($assigns) > 0){
    echo "<h3>Zuweisungen</h3>";
    foreach($assigns as $a){
        $u = $db->query("SELECT * FROM users WHERE id=".$a["user_id"])->fetch(PDO::FETCH_ASSOC);
        echo "<p>".$u["username"]."</p>";
    }
}
?>
<h3>Kommentar hinzufügen</h3>
<form method="post">
<textarea name="body"></textarea>
<input type="submit" name="add_comment" value="Kommentieren">
</form>
<h3>Zuweisen</h3>
<form method="post">
<select name="assignee">
<?php
$users = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach($users as $u){
    echo "<option value='".$u["id"]."'>".$u["username"]."</option>";
}
?>
</select>
<input type="submit" name="assign" value="Zuweisen">
</form>
<a href="edittodo.php?id=<?php echo $id; ?>">Bearbeiten</a> | <a href="deletetodo.php?id=<?php echo $id; ?>">Löschen</a> | <a href="index.php">Zurück</a>
</body></html>
