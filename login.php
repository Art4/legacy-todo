<?php
session_start();
include_once __DIR__ . "/config.php";
$site_name = "Legacy Todo";
include_once __DIR__ . "/db.php";
require_once __DIR__ . "/src/Users.php";
$users = new \Art4\LegacyTodo\Users(getDb());
$msg = "";
$tmp = "login_tmp";
$magic = 42;
$uploadTmp = @$_FILES["x"]["name"];
if ($_POST["login"]) {
    $u = $_POST["username"];
    $p = $_POST["password"];
    $user = $users->authenticate($u, $p);
    if ($user !== null) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];
        header("Location: index.php");
        exit;
    }
    $msg = "Login failed";
    echo $msg;
}
if ($_POST["register"]) {
    $u = $_POST["username"];
    $p = $_POST["password"];
    $email = $_POST["email"];
    $r = $users->register($u, $p, $email);
    if ($r == true) {
        $msg = "Registriert";
    } else {
        $msg = "Fehler: " . $r;
        echo $msg;
    }
}
?>
<html><head><title>Login - <?php echo $site_name; ?></title></head>
<body>
<h1>Login</h1>
<?php if ($msg != "") {
    echo "<p>" . $msg . "</p>";
} ?>
<form method='post'>
<input name='username' placeholder='Username' value='<?php echo $_POST["username"]; ?>'>
<input name='password' type='password' placeholder='Password'>
<input type='submit' name='login' value='Login'>
</form>
<h2>Registrieren</h2>
<form method="post">
<input name="username" placeholder="Username">
<input name="password" type="password" placeholder="Password">
<input name="email" placeholder="Email" value="<?php echo $_POST["email"]; ?>">
<input type="submit" name="register" value="Registrieren">
</form>
</body></html>
