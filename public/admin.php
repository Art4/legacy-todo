<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = new \Art4\LegacyTodo\AdminHandler($app);
echo $handler->handle($_POST);
