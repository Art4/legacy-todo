# ADR 0003: A `public/` webroot separates the HTTP surface from internals

## Status

Accepted

## Context

The repo root was also the Apache DocumentRoot, so every file was reachable as a static resource:
`database.sqlite` was downloadable in full (user table and password hashes) via `GET /database.sqlite`,
and `composer.lock`, PHPStan/Psalm baselines, `config.php`, `src/` and `vendor/` leaked tooling and
application internals. There was no boundary between "meant to be requested directly" (the 8 entry
points) and everything else — the webroot's contents were accidental, not deliberate. Filed directly
by the maintainer as issue #179.

## Decision

A `public/` directory is the only thing Apache serves. The 8 entry points move there and stay thin
front controllers over `src/` (unchanged URL surface). Everything internal stays at the repo root and
leaves the docroot by virtue of the docroot change alone — no intra-docroot access control:

- `public/` holds `index.php`, `todo.php`, `admin.php`, `addtodo.php`, `edittodo.php`,
  `deletetodo.php`, `login.php`, `logout.php`, and `uploads/` (user-uploaded files must stay
  servable at `/uploads/...`).
- Repo root keeps `src/`, `config.php`, `db.php`, `functions.php`, `includes/`, `vendor/`, tooling
  configs, `old/`, and the SQLite database.
- `run.sh up` keeps the repo mounted at `/var/www/html` and switches Apache's DocumentRoot to
  `/var/www/html/public` via a mounted vhost override (`docker/000-default.conf`).
- `Bootstrap::start()`'s default `db_file` is anchored to the repo root (`dirname(__DIR__) .
  '/database.sqlite'`). This is load-bearing: PHP's CWD follows the executed script's directory, so a
  CWD-relative default would have recreated the database inside `public/`, rebuilding the very
  exfiltration vector this move removes.
- Legacy files (`config.php`, `db.php`, `functions.php`, `includes/`) remain on disk and analysed by
  PHPStan/Rector, per ADR-0002's consequence list — they are simply no longer served.

## Consequences

- `GET /database.sqlite`, `/config.php`, `/composer.lock`, `/src/...`, `/vendor/...` etc. now return
  404 instead of the file contents.
- The 8 entry points reference `../src/Bootstrap.php` and `../includes/header.php`/`footer.php`
  (bootstrap stays at the repo root).
- Tooling path lists (PHPStan, Rector, Psalm baseline) point at `public/*.php`; Psalm's
  `<directory name="." />` already recurses into `public/`.
- A future scan must not re-propose moving entry points back to the repo root, nor proposing
  intra-docroot .htaccess-style protection as a substitute for the `public/` boundary.