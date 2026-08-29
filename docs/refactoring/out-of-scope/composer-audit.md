# Out of scope: Composer Audit

**Date:** 2026-08-29
**Candidate:** Tooling tree: Composer Audit (#34)
**Status:** wontfix

## Load-bearing reason

`composer audit` is a Composer 2.x command, and Composer 2.x requires PHP >= 7.2.5. This fixture is deliberately PHP 5.6-only (README.md, run.sh: PHP 5.6 only in Docker, no Composer on the host), so it cannot host Composer 2 or run `composer audit` in its own environment.

## Consequence for future scans

Same systemic wall as PHP CS Fixer, PHPUnit, PHPStan, and the Test Runner fallback. Do not re-propose Composer Audit.
