# PHPStan Level 6 — out of scope

Rejected by the maintainer on 2026-09-05. See PR #143 (branch `refactor/phpstan-level-6`) and issue #142.

## Reason

The maintainer closed PR #143 unmerged with this direction:

> Rejecting this direction, not requesting a rework. We want to reach
> structural-scan / real refactoring work sooner rather than keep climbing the
> PHPStan level chain — levels 2 through 5 produced zero real findings, and
> this level's own body shows only cosmetic missing-type findings captured
> into a regenerated baseline, not fixed. Closing PHPStan Level 6 as out of
> scope for now; the level chain stops here.

The PHPStan level chain therefore stops at level 5. Levels 6–10 are
permanently closed beneath this rejected required parent and must not be
proposed again.

## Reversal

Not a PHP-version block — this entry has no `**Blocked by:** PHP >= X.Y`
condition, so `tooling_tree.py` never auto-detects a reversal. Reversal only
by a human (or agent with a stated reason) removing this file.