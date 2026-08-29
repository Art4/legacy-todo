<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
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
    ]);

    $rectorConfig->sets([
        SetList::DEAD_CODE,
    ]);
};
