<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->addTodoHandler();
echo $handler->handle($_POST, $_FILES);
