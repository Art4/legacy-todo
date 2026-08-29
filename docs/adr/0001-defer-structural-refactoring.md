# ADR-0001: Defer agent-driven structural refactoring on the legacy fixture

**Status:** Accepted
**Date:** 2026-08-29

## Context

The continuous-refactoring loop's `structural-scan` gate is open on this repo: every leaf of the PHP tooling tree is resolved (out of scope for this fixture). The fixture is deliberately bad legacy PHP 5.6 code — god-methods with deep nesting (`functions.php`), duplicated logic, global mutable `$db` shared across every page script, and raw concatenated SQL.

The loop's design holds structural refactoring back until deterministic tooling has had its say, because a test suite and static analysis catch the silent regressions an agent-driven structural change can introduce. Here that guard is unavailable: PHPUnit, PHPStan, and PHP CS Fixer were all rejected as out of scope for this PHP-5.6-only fixture (see `docs/refactoring/out-of-scope/`), and the fixture's documented constraint is `php -l` as the only permitted check (README.md, run.sh).

## Decision

Do **not** perform agent-driven structural refactoring of this fixture while it has no regression test net. A structural change to the deliberately-rotten core (e.g. deepening `TodoManager` or consolidating the scattered todo persistence) cannot be verified as behavior-preserving against the retention rules in README.md without that net, and an unverified pass could silently break behaviour the fixture exists to exercise.

## Consequences

- A future `structural-scan` proposal on this repo should defer rather than attempt the walk-and-refactor, unless a test suite exists.
- Establishing that test suite is itself out of scope under the PHP-5.6-only constraint, so the safe path is not currently open.
- This is a load-bearing reason: re-attempting structural refactoring here without tooling contradicts this decision.
