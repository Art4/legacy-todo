<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->todoHandler();
echo $handler->handle((int) ($_GET["id"] ?? ""), $_GET, $_POST); // nosemgrep: php.lang.security.injection.echoed-request.echoed-request
// Suppression rationale: $get is relayed only into TodoHandler for the id (cast to int), the del_comment
// id (DB lookup) and Auth::redirect's "next" target (Location header + exit); $post feeds add_comment and
// assign, which are stored in the DB before a redirect. The echoed $out is the handler's rendered detail
// page, built purely from DB-fetched data (escaped via Layout::text()/attr()) and static markup. Nothing
// request-derived reaches the rendered HTML (ADR-0004, ADR-0013).
