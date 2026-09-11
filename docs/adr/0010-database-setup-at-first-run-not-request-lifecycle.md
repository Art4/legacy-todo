# ADR 0010: Database setup runs at first-run, not in the request lifecycle

## Status

Accepted

## Context

`Bootstrap::start()` (ADR-0002's composition root) called `createSchema()` — seven
`CREATE TABLE IF NOT EXISTS` statements — and `seedIfEmpty()` — a `COUNT(*)` plus up to eight
`INSERT` statements — on every single HTTP request, because the 8 front controllers all boot via
`start()`. SQLite's idempotent DDL made this safe but not free: it added unnecessary I/O to every
request and blurred the boundary between one-time infrastructure setup and runtime (issue #242,
Signal: *missing locality*).

## Decision

The schema/seed concern moves out of the request lifecycle into a dedicated first-run path:

- New `Installer` module (`src/Installer.php`) owns everything needed to make a database file
  request-ready: idempotent schema creation and seed-if-empty, behind one public `install()` method.
- `Bootstrap::install(array $config = [])` is the first-run static entry point beside `start()`,
  reusing the same db_file resolution (`config['db_file']` > `LEGACY_TODO_DB_FILE` > default
  `database.sqlite`) and `connect()`.
- `Bootstrap::start()` now only boots session, timezone, connects, and returns the module graph —
  it assumes a prepared database.
- `src/install.php` is the CLI entry point, invoked via `./run.sh install`; `run.sh up` runs it
  inline before starting the container (idempotent, so it is a no-op on an already-prepared DB).
- The E2E harness (`tests/Functional/E2eTestCase.php`) installs its fresh temp DB explicitly in
  `setUp()` before serving the first request.

## Consequences

- The request path no longer runs DDL or seed checks; a fresh database must be installed once before
  the app serves requests (`./run.sh install`, part of `up`).
- The DDL gets a single home (`Installer`) instead of the per-request duplication it had in
  `Bootstrap` and the per-test duplication it still has across `tests/Unit/*Test.php` fixtures —
  consolidating those test fixtures is a separate, noted follow-up, not part of this seam.
- `Bootstrap::install()` and the `Installer` module are the only place schema/seed lives; a future
  scan must not re-propose moving schema/seed back into the request lifecycle.