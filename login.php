<?php
session_start();
include "config.php";
include "db.php";
include "functions.php";
$msg = "";
$tmp = "login_tmp";
if($_POST["login"]){
    $u = $_POST["username"];
    $p = $_POST["password"];
    if(checkLogin($u,$p) == true){
        header("Location: index.php");
        exit;
    }else{
        $msg = "Login failed";
        echo $msg;
    }
}
if($_POST["register"]){
    $u = $_POST["username"];
    $p = $_POST["password"];
    $email = $_POST["email"];
    $r = registerUser($u,$p,$email);
    if($r == true){
        $msg = "Registriert";
    }else{
        $msg = "Fehler: ".$r;
        echo $msg;
    }
}
?>
<html><head><title>Login - <?php echo $site_name; ?></title></head>
<body>
<h1>Login</h1>
<?php if($msg != ""){ echo "<p>".$msg."</p>"; } ?>
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
