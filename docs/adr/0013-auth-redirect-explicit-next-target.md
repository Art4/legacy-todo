# ADR 0013: Auth::redirect takes the next target explicitly; superglobals stay in the front controllers

## Status

Accepted

## Context

ADR-0001 recorded the open-redirect fix: `Auth::redirect()` gained the private
`isSafeInAppTarget()` predicate and honoured the `next` return-navigation target only when it is
a proven in-app path, falling back to the handler's default target otherwise. Its recorded
consequence was that `$_GET["next"]` is read in exactly one place in the codebase —
`Auth::redirect()` itself.

That left the superglobal read inside Auth: `redirect($url)` silently consulted `$_GET["next"]`
itself, so a handler could not say *which* target it meant, and Auth reached into the request
layer it otherwise never touches. Issue #249 re-proposed this: a function whose job is safe
post-auth return navigation should not be the place a page reads `$_GET`. The pre-#240
open-redirect bug lived precisely because the `next` value crossed from the request into Auth
without the caller naming it.

## Decision

`Auth::redirect()` takes the next target explicitly:

- `redirect(string $url, string $next = "")` — the caller chooses whether a `next` target exists
  and what it is; Auth validates it with `isSafeInAppTarget()` (private, unchanged) and falls back
  to `$url` when it is not proven safe. Passing no `next` means "redirect to `$url`, no
  return-navigation override" — identical to the old empty-`next` case.
- The page handlers pass their own `$get["next"]` through: `TodoHandler` (which already received
  `$get`) and `EditTodoHandler` (which now gains the same `array $get` parameter as its sibling
  handlers, ADR-0012) relay it, and the front controllers (`public/edittodo.php`) hand `$_GET`
  over.
- `$_GET` is no longer read anywhere in `src/` — superglobal reads happen only in the front
  controllers, the single place a page is allowed to touch the request. `TodoHandler`'s business
  gate (`$get["next"] != ""`) keeps determining redirect-vs-rerender, not safety (ADR-0001).

## Consequences

- A future scan must not re-propose moving the `next`-target read back into `Auth::redirect()`, or
  any other superglobal read into `src/`: issue #249 is delivered by this ADR, and explicit
  parameter passing with superglobals confined to the front controllers is the settled shape of
  the seam (deepening ADR-0001, ADR-0012).
- Grep-verifiable: no `$_GET`/`$_POST`/`$_SESSION` read in `src/`; `Auth::redirect` has exactly two
  parameters, `$url` and `$next`.
- Behavior is unchanged: the same `next` values produce the same redirects; off-site,
  scheme-prefixed, protocol-relative, and backslash targets still fall back to the handler's
  default target.
- The `EditTodoHandler` seam is now identical to `TodoHandler`'s (both take `(int $id, array $get,
  array $post)`), and Psalm's taint analysis follows the explicit parameter rather than an
  invisible superglobal read.