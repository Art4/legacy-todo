<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->loginHandler();
echo $handler->handle($_GET, $_POST);
