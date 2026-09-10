<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

session_start();
$_SESSION = [];
session_destroy();
header("Location: login.php");
exit;
