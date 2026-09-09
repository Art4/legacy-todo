<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = new \Art4\LegacyTodo\TodoHandler($app);
echo $handler->handle((int) $_GET["id"], $_GET, $_POST);
