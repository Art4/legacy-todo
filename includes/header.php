<?php
$cfg = ['db_file' => 'database.sqlite', "siteName" => 'LegacyTodo', 'debug' => true];
$site_name = "Legacy Todo";
?>
<html><head><title><?php echo $site_name; ?> - <?php echo $cfg["siteName"]; ?></title>
<style>body{font-family:Arial}</style>
</head>
<body>
<div class="header">
<h2><?php echo $site_name; ?></h2>
<?php
if (isset($auth) && $auth->currentUser()["username"] != "") {
    echo "<p>Eingeloggt als " . $auth->currentUser()["username"] . "</p>";
}
?>
</div>
