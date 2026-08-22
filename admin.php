<?php
session_start();
include "config.php";
include "db.php";
include "functions.php";
include "helpers.php";
if($_SESSION["user_id"] == ""){
    header("Location: login.php");
    exit;
}
if($_SESSION["role"] != "admin"){
    echo "Keine Rechte";
    exit;
}
$tmp = "admin_tmp";
$data2 = "admin_wurst";
$magic = 42;
$tmpUpload = @$_FILES["upload"]["name"];
$kategorie = $_POST["kategorie"];
$cat = $_POST["cat"];
$category = $_POST["category"];
if($_POST["add_cat"]){
    $name = $kategorie;
    if($cat != ""){ $name = $cat; }
    if($category != ""){ $name = $category; } // inkonsistente Namen
    if($name == ""){
        echo "Name fehlt";
    }else{
        $mgr = new TodoManager();
        $mgr->handleTodo("category",array("name"=>$name,"user_id"=>$_SESSION["user_id"]));
    }
}
if($_POST["add_user"]){
    $u = $_POST["username"];
    $p = $_POST["password"];
    $role = $_POST["role"];
    $hash = md5($p);
    $db->exec("INSERT INTO users (username,password,role,email,created_at) VALUES ('".$u."','".$hash."','".$role."','".$u."@example.com','".date("Y-m-d")."')");
}
$users = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
$cats = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$tags = $db->query("SELECT * FROM tags")->fetchAll(PDO::FETCH_ASSOC);
if($_POST["add_tag"]){
    $tag = $_POST["tag"];
    $db->exec("INSERT INTO tags (name) VALUES ('".$tag."')");
}
?>
<html><head><title>Admin - <?php echo $site_name; ?></title></head>
<body>
<h1>Admin</h1>
<h2>Benutzer</h2>
<ul>
<?php foreach($users as $u){ echo "<li>".$u["username"]." - ".$u["role"]." - ".$u["email"]."</li>"; } ?>
</ul>
<form method="post">
<input name="username" placeholder="Username">
<input name="password" placeholder="Password">
<select name="role"><option value="user">user</option><option value="admin">admin</option></select>
<input type="submit" name="add_user" value="User anlegen">
</form>
<h2>Kategorien</h2>
<ul>
<?php foreach($cats as $c){ echo "<li>".$c["name"]."</li>"; } ?>
</ul>
<form method="post">
<input name="kategorie" placeholder="Kategorie (kategorie)">
<input name="cat" placeholder="cat">
<input name="category" placeholder="category">
<input type="submit" name="add_cat" value="Kategorie">
</form>
<h2>Tags</h2>
<ul>
<?php foreach($tags as $t){ echo "<li>".$t["name"]."</li>"; } ?>
</ul>
<form method="post">
<input name="tag" placeholder="Tag">
<input type="submit" name="add_tag" value="Tag">
</form>
<a href="index.php">Zurück</a>
</body></html>
