<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$app->auth()->logout();
session_destroy();
$app->auth()->redirect("login.php");
