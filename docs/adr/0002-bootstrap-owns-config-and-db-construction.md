# ADR 0002: Bootstrap owns config and DB construction

## Status

Accepted

## Context

Pages started by `Bootstrap::start()` loading `config.php`, `db.php` and `functions.php` from the startup path (issue #175). Those files carry the project's globals, secrets, and schema/seed logic, and their side effects (timezone, PDO connect/chmod, idempotent schema and seed) are needed by every page. Keeping them in the startup path makes `Bootstrap::start()` a copy of the legacy entry sequence instead of the owner of how a page starts.

`config.php`, `db.php` and `functions.php` deliberately stay on disk: PHPStan and Rector analyse them, and `tests/Legacy/LegacySmokeTest.php` requires `functions.php`. Only the page startup path was retired.

## Decision

`Bootstrap::start(array $config = [])` now boots the session and timezone, connects its own SQLite PDO (default `db_file=database.sqlite`), creates the schema and seeds it — all idempotent — and returns the module instances. The legacy files remain analysed and tested, but are no longer included from the startup path.

Goes with a process correction: when the `refactor-prioritize` Select step recorded the pending candidate directly to `main`, that commit was reverted (PR #176). Ledger and `bookkeeping.md` writes ride a branch/MR, never a direct commit to the default branch.

## Consequences

- A future scan should not re-propose absorbing `config.php`/`db.php`/`functions.php` into the startup path, nor deleting them from the repo while PHPStan/Rector/LegacySmokeTest still reference them.
- `start()` keeps a backward-compatible no-argument form; callers may pass `db_file` and `siteName` overrides.