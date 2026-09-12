<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->todoHandler();
echo $handler->handle((int) ($_GET["id"] ?? ""), $_GET, $_POST);
