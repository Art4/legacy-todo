<?php

session_start();
include_once __DIR__ . "/config.php";
include_once __DIR__ . "/db.php";
require_once __DIR__ . "/src/Auth.php";
$auth = new \Art4\LegacyTodo\Auth(getDb(), $_SESSION);
$auth->logout();
session_destroy();
header("Location: login.php");
exit;
