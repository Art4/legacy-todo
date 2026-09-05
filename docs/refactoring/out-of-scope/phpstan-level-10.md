# PHPStan Level 10 — out of scope

Permanently closed beneath the rejected `phpstan-level-6` required parent.
The maintainer closed the PHPStan level chain at level 5 on 2026-09-05 (PR
#143, branch `refactor/phpstan-level-6`, issue #142); see
`phpstan-level-6.md` in this directory for the decision and its reasoning.

## Reason

The PHPStan level chain stops at level 5: "Levels 6–10 are permanently closed
beneath this rejected required parent and must not be proposed again"
(`phpstan-level-6.md` in this directory). This node-level record is the
machine-readable form of that closure for `phpstan-level-10`, the level
chain's `php-structural-scan` leaf (`phpstan.md`). Without it the leaf reads
as neither fulfilled nor rejected, which keeps `composer-audit`'s "every other
leaf feeding `php-structural-scan` is resolved" stop-condition
(`composer-audit.md`) permanently unsatisfied and `structural-scan` closed.

## Reversal

Not a PHP-version block - this entry has no `**Blocked by:** PHP >= X.Y`
condition, so `tooling_tree.py` never auto-detects a reversal. Reversal only
by a human (or agent with a stated reason) removing this file alongside
`phpstan-level-6.md`.