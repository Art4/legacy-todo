# Out of scope: PHPUnit

**Date:** 2026-08-29
**Candidate:** Tooling tree: PHPUnit (#32)
**Status:** wontfix

## Load-bearing reason

The only PHPUnit version compatible with this fixture's PHP 5.6 (5.7.x) is blocked by Composer's security-advisory gate (PKSA-z3gr-8qht-p93v); the advisories are fixed only in versions requiring PHP >= 7.0. This is incompatible with the fixture's deliberate constraints (README.md, run.sh): PHP 5.6 only, no Composer on the host.

Onboarding a known-vulnerable test runner, or forcing a PHP upgrade to get a secure one, both contradict the fixture's documented premise.

## Consequence for future scans

Shared with the rest of the PHP tooling tree (PHP CS Fixer, PHPStan, Composer Audit): modern deterministic PHP tooling requires a PHP version newer than this fixture's deliberate 5.6. Do not re-propose PHPUnit; expect the sibling leaves to require the same treatment.
