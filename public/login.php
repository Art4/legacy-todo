<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = new \Art4\LegacyTodo\LoginHandler($app);
echo $handler->handle($_POST);
