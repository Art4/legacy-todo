<?php
require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$auth = $app->auth();
$users = $app->users();
$msg = "";
$tmp = "login_tmp";
$magic = 42;
$uploadTmp = @$_FILES["x"]["name"];
if ($_POST["login"]) {
    $u = $_POST["username"];
    $p = $_POST["password"];
    if ($auth->login($u, $p)) {
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
<html><head><title>Login - <?php echo $app->siteName(); ?></title></head>
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
