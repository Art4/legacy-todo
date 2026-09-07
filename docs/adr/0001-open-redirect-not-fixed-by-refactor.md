# ADR 0001: Do not fix the open-redirect under the refactor label

## Status

Accepted

## Context

The existing `$_GET["next"]` redirect in `edittodo.php` and `todo.php` does not validate the target URL — an unvalidated redirect is a known open-redirect vulnerability. The Auth module refactor (issue #171) replicates this behaviour faithfully to remain behaviour-preserving.

## Decision

The open-redirect is reproduced as-is. Fixing it is a behaviour change, not a structural refactor, and belongs on the normal bug path — not shipped under the `refactor:candidate` label. A future scan should not re-suggest fixing it as refactor work.

## Consequences

- The open-redirect persists until a separate bug-fix addresses it.
- Auth's `redirect()` method deliberately accepts an unvalidated URL to match existing semantics.
