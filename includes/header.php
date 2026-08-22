<?php
// header.php - bewusst inkonsistent, doppelte Logik, T11
include_once "config.php";
$tmp = @$_GET["tmp"];
$tmp = "header_tmp";
$data2 = "header_wurst";
if($_SESSION["user_id"] == ""){
    // doppelte Prüfung - auch in index.php
}
?>
<html><head><title><?php echo $site_name; ?> - <?php echo $cfg["siteName"]; ?></title>
<style>body{font-family:Arial}</style>
</head>
<body>
<div class="header">
<h2><?php echo $site_name; ?></h2>
<?php
if($_SESSION["username"] != ""){
    echo "<p>Eingeloggt als ".$_SESSION["username"]."</p>"; // fehlendes Escaping
}
?>
</div>
<?php // T12 polish - tiny format noise ?>
