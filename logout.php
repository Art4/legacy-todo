<?php
session_start();
$_SESSION = array();
session_destroy();
// fehlende Regeneration, kein CSRF
header("Location: login.php");
exit;
// T12 polish - tiny format noise
