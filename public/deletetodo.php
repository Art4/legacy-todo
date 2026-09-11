<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->deleteTodoHandler();
echo $handler->handle((int) $_GET["id"], $_GET);
