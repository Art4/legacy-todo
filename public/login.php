<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$page = new \Art4\LegacyTodo\Page($app);
echo $page->login($_POST);
