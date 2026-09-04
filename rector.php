<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/addtodo.php',
        __DIR__ . '/admin.php',
        __DIR__ . '/config.php',
        __DIR__ . '/db.php',
        __DIR__ . '/deletetodo.php',
        __DIR__ . '/edittodo.php',
        __DIR__ . '/functions.php',
        __DIR__ . '/helpers.php',
        __DIR__ . '/includes',
        __DIR__ . '/index.php',
        __DIR__ . '/login.php',
        __DIR__ . '/logout.php',
        __DIR__ . '/todo.php',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_56,
    ]);
