<?php
session_start();
$_SESSION = array();
session_destroy();
// fehlende Regeneration, kein CSRF
header("Location: login.php");
exit;
