<?php

/**
 * One-time database setup entry point (ADR-0010). Idempotent — safe to run on
 * an already-installed database. Invoked via `./run.sh install` or CI; never
 * from the request lifecycle.
 */

require_once __DIR__ . '/../vendor/autoload.php';

\Art4\LegacyTodo\Bootstrap::install();
