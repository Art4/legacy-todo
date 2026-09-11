# ADR 0001: Fix the open-redirect inside the Auth seam

## Status

Superseded — replaced by the open-redirect fix shipped in candidate #240.

## Context

The existing `$_GET["next"]` redirect in `edittodo.php` and `todo.php` does not validate the target URL — an unvalidated redirect is a known open-redirect vulnerability. The Auth module refactor (issue #171) replicated this behaviour faithfully to remain behaviour-preserving.

ADR 0001 originally decided the open-redirect stays as-is because the refactor must preserve existing semantics, and fixing it is a behaviour change. Candidate #240 re-proposed the fix as a Security-signalled structural candidate, and the refactoring-design grilling determined that the deepened module itself is the correct fix: `Auth::redirect()`'s one job is safe post-auth return navigation, and a return navigator that open-redirects fails its own job.

## Decision

The open-redirect is now closed inside `Auth::redirect()` via the private `isSafeInAppTarget()` predicate. The `$_GET["next"]` override is honoured only when it is a proven in-app relative path — empty, `://`-bearing, scheme-prefixed, protocol-relative (`//`), and backslash values fall back to the handler's supplied default target. The seam deepened in-place: interface complexity stays unchanged (`redirect($url): void`, one parameter), while the validation rule and fallback are entirely internal to Auth.

A future scan must not re-propose fixing the open redirect as separate refactor or bug work — it is already fixed at the correct seam.

## Consequences

- The open-redirect is closed. All existing in-app redirect flows remain unchanged; only attacker-controlled off-site, scheme-prefixed, protocol-relative, and backslash targets are rejected.
- Every present and future handler that calls `Auth::redirect()` inherits the safe redirect by construction — no per-handler `next` validation is needed.
- `$_GET["next"]` is read in exactly one place in the codebase (`Auth::redirect()`). The `requireLogin()` producer side is unchanged; `TodoHandler`'s business gate (`$get["next"] != ""`) stays and continues to determine redirect-vs-rerender, not safety.
