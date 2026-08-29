# Out of scope: PHP CS Fixer

**Date:** 2026-08-29
**Candidate:** Tooling tree: PHP CS Fixer (#30)
**Status:** wontfix

## Load-bearing reason

PHP CS Fixer's dependency tree (via `symfony/process`) requires runtime PHP >= 7.2.5, which is fundamentally incompatible with this fixture's deliberate constraints (README.md, run.sh):

- PHP 5.6 only, running exclusively in Docker
- no Composer on the host
- `php -l` is the documented "einzig erlaubter Check" (only permitted check)

Adopting PHP CS Fixer would require an ancient, unsupported 1.x/2.x version plus Docker-only orchestration, and would directly contradict the fixture's documented `php -l`-only lint rule.

## Consequence for future scans

The same PHP >= 7.2 requirement applies to the other modern deterministic PHP tooling leaves (PHPUnit, PHPStan, Composer Audit). This is a systemic incompatibility between the PHP tooling tree and this deliberately-legacy PHP 5.6 fixture, not specific to PHP CS Fixer. Do not re-propose PHP CS Fixer; expect the sibling leaves to require the same treatment.
