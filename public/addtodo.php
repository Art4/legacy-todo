<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = new \Art4\LegacyTodo\AddTodoHandler($app);
echo $handler->handle($_POST, $_FILES);
