<?php
session_start();
include_once __DIR__ . "/config.php";
include_once __DIR__ . "/db.php";
$db = getDb();
include_once __DIR__ . "/functions.php";
if ($_SESSION["user_id"] == "") {
    header("Location: login.php?next=addtodo.php");
    exit;
}
$msg = "";
$tmp = "addtodo";
$cat = $_POST["category_id"];
$kategorie = $_POST["kategorie"];
$data2 = "addtodo_noise";
if ($_POST["save"]) {
    $title = $_POST["title"];
    $text = $_POST["text"];
    $priority = $_POST["priority"];
    $due = $_POST["due_date"];
    $userId = $_SESSION["user_id"];
    $file = $_FILES["upload"]["name"];
    if ($file != "") {
        $dest = "uploads/" . $file;
        @move_uploaded_file($_FILES["upload"]["tmp_name"], $dest);
        echo "Upload: " . $file;
    }
    // Titelpflicht
    if ($title == "") {
        $msg = "Titel erforderlich";
        echo $msg;
    } else {
        $r = createTodo($title, $text, $priority, $due, $userId);
        if ($r == true) {
            header("Location: index.php");
            exit;
        }
        $msg = "Fehler";
        echo $msg;
    }
}
$x = $_FILES["upload"]["name"];
$data2 = $x;
?>
<html><head><title>Neues Todo</title></head>
<body>
<h1>Todo erstellen</h1>
<?php if ($msg !== "") {
    echo "<p>" . $msg . "</p>";
} ?>
<form method='post' enctype='multipart/form-data'>
<input name='title' placeholder='Titel' value='<?php echo $_POST["title"]; ?>'>
<textarea name='text'><?php echo $_POST["text"]; ?></textarea>
<select name='priority'>
<option value='1'>Hoch</option>
<option value='2' selected>Normal</option>
<option value='3'>Niedrig</option>
</select>
<select name='category_id'>
<?php
$cats = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cats as $c) {
    echo "<option value='" . $c["id"] . "'>" . $c["name"] . "</option>";
}
?>
</select>
<input name='due_date' type='date' value='<?php echo $_POST["due_date"]; ?>'>
<input type='file' name='upload'>
<input type='submit' name='save' value='Speichern'>
</form>
<a href="index.php">Zurück</a>
</body></html>
