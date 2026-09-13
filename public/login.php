<?php

require_once __DIR__ . "/../src/Bootstrap.php";
$app = \Art4\LegacyTodo\Bootstrap::start();
$handler = $app->loginHandler();
echo $handler->handle($_GET, $_POST); // nosemgrep: php.lang.security.injection.echoed-request.echoed-request
// Suppression rationale: $get feeds only Auth::redirect("index.php", $get["next"] ?? ""), which sends a
// Location header and exits before this render; $post re-feeds username/email into the form, escaped via
// Layout::attr()/text(). The echoed $out is built from static markup and Layout-escaped fields; nothing
// request-derived reaches the rendered HTML (ADR-0013, ADR-0014).
