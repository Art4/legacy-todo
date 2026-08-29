# Out of scope: Test Runner (fallback)

**Date:** 2026-08-29
**Candidate:** Tooling tree: Test Runner (fallback) (#36)
**Status:** wontfix

## Load-bearing reason

Its only fulfillment path is adopting a test runner, defaulting to PHPUnit — which is already rejected as out of scope for this fixture's deliberate PHP-5.6-only constraint. No compatible test runner exists for the legacy runtime.

## Consequence for future scans

Same systemic wall as PHP CS Fixer, PHPUnit, Composer Audit, and PHPStan. Do not re-propose the Test Runner (fallback) node.
