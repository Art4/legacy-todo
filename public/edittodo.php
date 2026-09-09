<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = new \Art4\LegacyTodo\EditTodoHandler($app);
echo $handler->handle((int) $_GET["id"], $_POST);
