<?php

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/public/addtodo.php',
        __DIR__ . '/public/admin.php',
        __DIR__ . '/public/deletetodo.php',
        __DIR__ . '/public/edittodo.php',
        __DIR__ . '/public/index.php',
        __DIR__ . '/public/login.php',
        __DIR__ . '/public/logout.php',
        __DIR__ . '/public/todo.php',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withTypeCoverageLevel(0)
    ->withSkip([
        // This rule only rejects scalar mismatches it can prove. Values it sees
        // as mixed (request input, database rows) stay unchecked, so a call that
        // PHP coerces today could become a TypeError at runtime under
        // strict_types, as in issue #300. Adding the declare to the application
        // is a change in behaviour, not a rewrite, and stays out of this config.
        Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class,
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_74,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::EARLY_RETURN,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);
