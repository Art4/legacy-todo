# ADR 0010: Adopt PSR-4 Autoloading

## Status

Accepted

## Context

By issue #230, the app's own source code had no PSR-4 namespace mapping: every
file was loaded through manual `require_once` chains keyed by filesystem path.
The codebase is flat and legacy — unnamespaced files at the repo root, wired to
each other by hand — so "adopt PSR-4" had to be scoped to the *mechanism*, not
a full one-shot migration of every file (which would be one large, disruptive
MR). The node's two existing incidental appearances in this tree — `phpunit.md`'s
`tests/Unit/` test-layout convention and `phpstan.md`'s `paths` resolution — do
not model "does the app's own source have a real, working PSR-4 mapping" as an
adoptable, checkable step, so a fresh node was warranted.

## Decision

Adopt the PSR-4 autoloading mechanism on the narrow bar covered by both
Fulfilment criteria:

- Declare `autoload.psr-4` in `composer.json`, mapping the root namespace
  `Art4\LegacyTodo\` to `src/`.
- Migrate exactly one real file into the mapping as proof the mechanism works
  (namespace it, move it into `src/` if not already there, update its one call
  site) — a low, deliberately incomplete threshold that unlocks autoloading
  without demanding the full migration up front.
- Wire `require_once` of `vendor/autoload.php` into the composition root /
  every entry point, so *new* code never needs another manual require. Existing
  manual `require_once` chains for not-yet-namespaced files stay untouched.

## Consequences

- New code can rely on class autoloading instead of manual path requires —
  `namespace Art4\LegacyTodo;` in `src/` resolves by the PSR-4 mapping.
- Every remaining unnamespaced file is an ordinary friction signal for
  `structural-scan` to discover and migrate incrementally — this node neither
  owns nor tracks the rest of the migration, and its wiring step never removes
  an existing manual `require_once`.
- A future scan must not re-litigate the decision to adopt PSR-4, or re-propose
  a wholesale file-by-file migration as this node's own scope: issue #230 is
  delivered by this MR. `src/Bootstrap.php` is the composition root through
  which the autoloader is wired (ADR-0009, ADR-0002).