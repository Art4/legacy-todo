<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// src/Bootstrap.php starts with a statement outside its class (it requires the
// autoloader for the entry scripts in public/). PHPUnit cannot be told that a
// test uses a statement outside a class, so with strict coverage metadata the
// first test to load the file would be flagged, and which test that is depends
// on the run order. Loading the file here, before any test runs, keeps that
// statement out of every test.
class_exists(\Art4\LegacyTodo\Bootstrap::class);
