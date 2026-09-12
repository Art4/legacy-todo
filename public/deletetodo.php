<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->deleteTodoHandler();
echo $handler->handle((int) ($_GET["id"] ?? ""), $_GET); // nosemgrep: php.lang.security.injection.echoed-request.echoed-request
// Suppression rationale: $get is relayed only into DeleteTodoHandler for the id (cast to int) and the
// confirm flag ("1" comparison); the confirm path archives and redirects via header()/exit before any
// render. The echoed $out is built purely from DB-fetched todo data (escaped via Layout::text()/attr())
// and static markup. Nothing request-derived reaches the rendered HTML; the array is passed to mirror the
// handler seam, not echoed (ADR-0004).
