<?php

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/addtodo.php',
        __DIR__ . '/admin.php',
        __DIR__ . '/config.php',
        __DIR__ . '/db.php',
        __DIR__ . '/deletetodo.php',
        __DIR__ . '/edittodo.php',
        __DIR__ . '/functions.php',
        __DIR__ . '/src/Helpers.php',
        __DIR__ . '/includes',
        __DIR__ . '/index.php',
        __DIR__ . '/login.php',
        __DIR__ . '/logout.php',
        __DIR__ . '/todo.php',
        __DIR__ . '/tests',
    ])
    ->withTypeCoverageLevel(0)
    ->withSkip([
        // strict_types doesn't exist before PHP 7.0 -- this codebase's real
        // runtime floor is still 5.6 (see issue #156 for the pending
        // migration to 7.4). A per-file skip here (functions.php only) once
        // chased this rule file by file as each one happened to become
        // newly eligible -- it re-broke the runtime on config.php/logout.php
        // (issue #158) the moment the rule found a new opening. Skipped
        // globally instead: nothing in this codebase should gain
        // strict_types until the floor genuinely moves.
        Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class,
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_56,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::EARLY_RETURN,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);
