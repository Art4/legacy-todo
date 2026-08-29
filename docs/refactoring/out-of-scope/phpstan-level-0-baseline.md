# Out of scope: PHPStan Level 0

**Date:** 2026-08-29
**Candidate:** Tooling tree: PHPStan Level 0 (#35)
**Status:** wontfix

## Load-bearing reason

PHPStan requires PHP >= 7.1 (current 2.x requires `^7.4|^8.0`) and cannot be installed under this fixture's deliberate PHP-5.6-only constraint (README.md, run.sh).

## Consequence for future scans

Same systemic wall as PHP CS Fixer, PHPUnit, Composer Audit, and the Test Runner fallback. Do not re-propose PHPStan Level 0.
