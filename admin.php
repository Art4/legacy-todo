<?php
session_start();
include_once __DIR__ . "/config.php";
$site_name = "Legacy Todo";
include_once __DIR__ . "/db.php";
include_once __DIR__ . "/functions.php";
require_once __DIR__ . "/src/Auth.php";
require_once __DIR__ . "/src/Users.php";
require_once __DIR__ . "/src/Taxonomy.php";
$usersRepo = new \Art4\LegacyTodo\Users(getDb());
$taxonomyRepo = new \Art4\LegacyTodo\Taxonomy(getDb());
$auth = new \Art4\LegacyTodo\Auth(getDb(), $_SESSION);
$auth->requireLogin();
$auth->requireRole("admin");
$tmp = "admin_tmp";
$data2 = "admin_wurst";
$magic = 42;
$tmpUpload = @$_FILES["upload"]["name"];
$kategorie = $_POST["kategorie"];
$cat = $_POST["cat"];
$category = $_POST["category"];
if ($_POST["add_cat"]) {
    $name = $kategorie;
    if ($cat != "") {
        $name = $cat;
    }
    if ($category != "") {
        $name = $category;
    }
    if ($name == "") {
        echo "Name fehlt";
    } else {
        $taxonomyRepo->createCategory($name, $auth->currentUser()["user_id"]);
    }
}
if ($_POST["add_user"]) {
    $u = $_POST["username"];
    $p = $_POST["password"];
    $role = $_POST["role"];
    $usersRepo->create($u, $p, $role);
}
$users = $usersRepo->listAll();
$cats = $taxonomyRepo->listCategories();
$tags = $taxonomyRepo->listTags();
if ($_POST["add_tag"]) {
    $tag = $_POST["tag"];
    $taxonomyRepo->createTag($tag);
}
?>
<html><head><title>Admin - <?php echo $site_name; ?></title></head>
<body>
<h1>Admin</h1>
<h2>Benutzer</h2>
<ul>
<?php foreach ($users as $u) {
    echo "<li>" . $u["username"] . " - " . $u["role"] . " - " . $u["email"] . "</li>";
} ?>
</ul>
<form method="post">
<input name="username" placeholder="Username">
<input name="password" placeholder="Password">
<select name="role"><option value="user">user</option><option value="admin">admin</option></select>
<input type="submit" name="add_user" value="User anlegen">
</form>
<h2>Kategorien</h2>
<ul>
<?php foreach ($cats as $c) {
    echo "<li>" . $c["name"] . "</li>";
} ?>
</ul>
<form method="post">
<input name="kategorie" placeholder="Kategorie (kategorie)">
<input name="cat" placeholder="cat">
<input name="category" placeholder="category">
<input type="submit" name="add_cat" value="Kategorie">
</form>
<h2>Tags</h2>
<ul>
<?php foreach ($tags as $t) {
    echo "<li>" . $t["name"] . "</li>";
} ?>
</ul>
<form method="post">
<input name="tag" placeholder="Tag">
<input type="submit" name="add_tag" value="Tag">
</form>
<a href="index.php">Zurück</a>
</body></html>
