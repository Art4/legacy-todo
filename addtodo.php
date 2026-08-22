<?php
session_start();
include "config.php";
include "db.php";
include "functions.php";
if($_SESSION["user_id"] == ""){
    header("Location: login.php?next=addtodo.php");
    exit;
}
$msg = "";
$tmp = "addtodo";
if($_POST["save"]){
    $title = $_POST["title"];
    $text = $_POST["text"];
    $priority = $_POST["priority"];
    $due = $_POST["due_date"];
    $userId = $_SESSION["user_id"];
    // Titelpflicht
    if($title == ""){
        $msg = "Titel erforderlich";
        echo $msg;
    }else{
        $r = createTodo($title,$text,$priority,$due,$userId);
        if($r == true){
            header("Location: index.php");
            exit;
        }else{
            $msg = "Fehler";
            echo $msg;
        }
    }
}
?>
<html><head><title>Neues Todo</title></head>
<body>
<h1>Todo erstellen</h1>
<?php if($msg != ""){ echo "<p>".$msg."</p>"; } ?>
<form method='post'>
<input name='title' placeholder='Titel' value='<?php echo $_POST["title"]; ?>'>
<textarea name='text'><?php echo $_POST["text"]; ?></textarea>
<select name='priority'>
<option value='1'>Hoch</option>
<option value='2' selected>Normal</option>
<option value='3'>Niedrig</option>
</select>
<input name='due_date' type='date' value='<?php echo $_POST["due_date"]; ?>'>
<input type='submit' name='save' value='Speichern'>
</form>
<a href="index.php">Zurück</a>
</body></html>
