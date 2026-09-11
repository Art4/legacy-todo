# ADR 0011: PHPUnit and E2E tests run against the PHP 7.4 runtime floor and an 8.2 upgrade-prep version

## Status

Accepted

## Context

The app's real runtime is PHP 7.4 (`run.sh`'s `php:7.4-apache`) and `composer.json` declares a
`>=7.4.0` floor, yet CI's test jobs ran under `php:8.3-cli` via the ordinary dev-dep stack
(PHPUnit `^11`, which requires PHP 8.2+) — so the suite was verified against neither the real
runtime version nor the version a future upgrade would land on. Issue #239 closed that gap with a
version matrix, mirroring the #89 → #156 5.6 → 7.4 migration precedent: dual-version test coverage
first, the runtime migration itself as a separate, later ticket.

## Decision

The `phpunit` job (`Unit`, `Legacy` testsuites) and the `e2e` job (`Functional` testsuite) both run
under `php:7.4` and `php:8.2` as a required CI matrix. The 7.4 leg pins `config.platform.php` to
7.4.33, swaps `phpunit/phpunit` to `^9`, drops psalm, and runs via `phpunit-9.xml.dist`; the 8.2
leg runs the ordinary lockfile. Static analysis (PHPStan, Rector, Psalm taint, PHPMD, Composer
audit, coverage floor) stays on PHP 8.3, untouched. The 8.2 upgrade-prep target is deliberate per
#239's explicit request — two steps above the 7.4 floor, one below the 8.3 marker — not a mismatch
to be "aligned". `run.sh`'s `lint` subcommand (`php -l`) is dropped as redundant: syntax validity is
already covered by PHPStan, and the 7.4 leg's real PHPUnit run already requires 7.4-valid syntax to
load.

## Consequences

- The suite is now verified under the real 7.4 runtime floor and under 8.2 as upgrade prep; the
  runtime itself (`php:7.4-apache`) and the declared `>=7.4.0` floor stay untouched.
- The `docs/refactoring/out-of-scope/php-minimal-version.md` reversal marker (`Blocked by:
  PHP >= 8.3.0`) is NOT satisfied by the 8.2 legs — it reopens only when the declared floor
  actually reaches 8.3. A future scan must not treat 8.2 test coverage as fulfilling that node,
  must not collapse the matrix back to a single version, and must not raise the declared floor
  ahead of the real runtime upgrade.