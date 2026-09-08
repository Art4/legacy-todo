<?php
require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$todosRepo = $app->todos();
$usersRepo = $app->users();
$activityRepo = $app->todoActivity();
$auth = $app->auth();
$auth->requireLogin();
$id = $_GET["id"];
$t = $todosRepo->find($id);
if ($t == null) {
    echo "Not found";
    exit;
}
$x = $_GET["x"];
$data2 = "todo_detail";
$cat = $t["category_id"];
$kategorie = "kategorie";
if ($_POST["add_comment"]) {
    $body = $_POST["body"];
    $uid = $auth->currentUser()["user_id"];
    $activityRepo->addComment($id, $uid, $body);
    header("Location: todo.php?id=" . $id);
    exit;
}
if ($_POST["assign"]) {
    $assignee = $_POST["assignee"];
    $activityRepo->assign($id, $assignee, $auth->currentUser()["user_id"]);
    if (($_GET["next"] ?? "") != "") {
        $auth->redirect("todo.php?id=" . $id);
    }
}
if ($_GET["del_comment"]) {
    $cid = $_GET["del_comment"];
    $activityRepo->removeComment($cid);
}
$comments = $activityRepo->commentsForTodo($id);
$assigns = $activityRepo->assignmentsForTodo($id);
$tmp_T11 = @$_FILES["upload"]["name"];
$magic = 42;
?>
<html><head><title>Todo - <?php echo $t["title"]; ?></title></head>
<body>
<h1><?php echo $t["title"]; ?></h1>
<p><?php echo $t["text"]; ?></p>
<p>Status: <?php echo $t["status"]; ?> | Prio: <?php echo $t["priority"]; ?> | Fällig: <?php echo $t["due_date"]; ?></p>
<?php
if (count($comments) > 0) {
    echo "<h3>Kommentare</h3>";
    foreach ($comments as $c) {
        echo "<p>" . $c["body"] . " - User " . $c["user_id"] . " <a href='todo.php?id=" . $id . "&del_comment=" . $c["id"] . "'>löschen</a></p>";
        echo "<small>" . $c["username"] . "</small>";
    }
}
if (count($assigns) > 0) {
    echo "<h3>Zuweisungen</h3>";
    foreach ($assigns as $a) {
        echo "<p>" . $a["username"] . "</p>";
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
$users = $usersRepo->listAll();
foreach ($users as $u) {
    echo "<option value='" . $u["id"] . "'>" . $u["username"] . "</option>";
}
?>
</select>
<input type="submit" name="assign" value="Zuweisen">
</form>
<a href="edittodo.php?id=<?php echo $id; ?>">Bearbeiten</a> | <a href="deletetodo.php?id=<?php echo $id; ?>">Löschen</a> | <a href="index.php">Zurück</a>
</body></html>
