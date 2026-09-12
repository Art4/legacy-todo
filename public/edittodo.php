<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->editTodoHandler();
echo $handler->handle((int) ($_GET["id"] ?? ""), $_GET, $_POST); // nosemgrep: php.lang.security.injection.echoed-request.echoed-request
// Suppression rationale: $get is relayed only into Auth::redirect("todo.php?id=" . $id, $get["next"] ?? ""),
// which sends a Location header and exits before this render; the echoed $out is built purely from the
// DB-fetched todo (escaped via Layout::attr()/text()) and static markup. Nothing request-derived reaches
// the rendered HTML; the array is passed to mirror TodoHandler's seam, not echoed (ADR-0013).
