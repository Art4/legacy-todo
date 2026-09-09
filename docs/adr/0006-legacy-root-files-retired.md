# ADR 0006: The legacy root bootstrap files are retired from the repo

## Status

Accepted

## Context

ADR-0002 retired `config.php`, `db.php` and `functions.php` from the page startup path, but kept them
on disk: PHPStan and Rector analysed them, and `tests/Legacy/LegacySmokeTest.php` required
`functions.php`. Its consequences clause guarded a future scan from re-proposing deleting them *while
those tooling/test references still existed*. The legacy `old/` directory (`backup.php`,
`todo_old.php`) lingered on the same basis.

That precondition has since been met: the tooling paths (Rector, PHPStan, Psalm, PHPUnit, PHPMD,
php-cs-fixer) are what kept the dead files reachable, and nothing in the live code (`src/`, `public/`,
`tests/`) includes them anymore.

## Decision

The dead legacy surface is deleted from the repo in one MR (#203, issue #200): `config.php`,
`db.php`, `functions.php`, `old/`, and `tests/Legacy/LegacySmokeTest.php` are removed, and every
remaining reference in the tooling configuration (`rector.php`, `phpstan.neon`, `psalm.xml`,
`phpunit.xml.dist`, `phpmd.xml.dist`, `.php-cs-fixer.dist.php`) and the webroot-guard test's
internal-file list goes with them. `config.php`'s dummy secrets leave the repo with it.

## Consequences

- A future scan must not re-propose re-adding these files, nor restoring them to any tooling path —
  the startup path is Bootstrap's, and there is no legacy root surface left to analyse.
- ADR-0002's "stay on disk" clause is fully discharged; the tooling configuration now describes only
  live code.