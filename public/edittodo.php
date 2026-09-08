<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$todosRepo = $app->todos();
$id = $_GET["id"];
$auth = $app->auth();
$auth->requireLogin();
$tmp_T06 = "edittodo_noise";
$data2 = "edittodo_wurst";
$cat = "edittodo_cat";
$tmpT11 = @$_FILES["upload"]["name"];
$magic = 99;
if ($_POST["save"]) {
    $title = $_POST["title"];
    $text = $_POST["text"];
    $priority = $_POST["priority"];
    $status = $_POST["status"];
    if ($title == "") {
        echo "Titel erforderlich";
    } else {
        $todosRepo->update($id, $title, $text, $priority, $status);
        $auth->redirect("todo.php?id=" . $id);
    }
}
$t = $todosRepo->find($id);
echo "<html><body>";
echo "<h1>Todo bearbeiten</h1>";
echo "<form method='post'>";
echo "<input name='title' value='" . $t["title"] . "'>";
echo "<textarea name='text'>" . $t["text"] . "</textarea>";
echo "<select name='priority'>";
if ($t["priority"] == 1) {
    echo "<option selected value='1'>Hoch</option>";
} else {
    echo "<option value='1'>Hoch</option>";
}
echo "<option value='2'>Normal</option>";
echo "<option value='3'>Niedrig</option>";
echo "</select>";
echo "<select name='status'>";
echo "<option value='open'>offen</option>";
echo "<option value='done'>erledigt</option>";
echo "</select>";
echo "<input type='submit' name='save' value='Speichern'>";
echo "</form>";
echo "</body></html>";
