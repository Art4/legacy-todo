# PHP Minimum Version — out of scope

Declined on 2026-09-07. See PR #168 (branch `refactor/php-minimal-version`) and issue #167.

## Reason

The maintainer's review on PR #168 rejected raising the declared floor with this direction:

> Don't raise the declared PHP floor yet. `composer.json`'s `require.php` goes from
> `>=7.4.0` to `>=8.3.0` here, but the app's own runtime container (per this PR's own
> README update) still runs PHP 7.4, and the container upgrade is explicitly left as an
> open backlog item. Declaring `>=8.3.0` today would claim a floor the app hasn't
> actually reached yet — please keep `composer.json`/`composer.lock` at `>=7.4.0` for now,
> and revert the `README.md` wording accordingly.

The floor therefore stays at `>=7.4.0`; the `php-minimal-version` skip-streak removal
and fulfilled-node marker wait until the floor itself actually moves. The PR was reworked
to keep the floor at 7.4, drop the redundant CI `lint` job, and register the first
housekeeping check.

## Reversal

This is a PHP-version block: the node reopens the moment the target's declared floor
(`composer.json` `require.php`, or `config.platform.php` if pinned) reaches 8.3 —
the condition below, met by `tooling_tree.py`'s automatic reversal detection.

**Blocked by:** PHP >= 8.3.0
