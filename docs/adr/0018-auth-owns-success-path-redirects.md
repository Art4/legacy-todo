# ADR 0018: Auth owns every redirect, including mutation success paths

## Status

Accepted

## Context

ADR-0013 made `Auth::redirect(string $url, string $next = "")` the redirect
primitive, but only the `next`-aware call sites reached it (`TodoHandler`'s
assign branch, `EditTodoHandler`, `LoginHandler`). Four mutation success paths
still composed their own `header("Location: ..."); exit;` inline:
`DeleteTodoHandler` (archive confirm), `AddTodoHandler` (create),
`TodoHandler` (add comment) and `public/logout.php`. ADR-0016 had already
given the failure side one owner (`Layout::errorPage()`); the success side was
the remaining asymmetry. Issue #267.

## Decision

`Auth::redirect()` is the only place a `Location` header is emitted. The four
raw call sites now call it without a `$next`, so the emitted header is
byte-identical to before and `isSafeInAppTarget()` is never consulted:

- `DeleteTodoHandler` -> `$this->auth->redirect("index.php")`
- `AddTodoHandler` -> `$this->auth->redirect("index.php")`
- `TodoHandler` add-comment -> `$this->auth->redirect("todo.php?id=" . $id)`
- `public/logout.php` -> `$app->auth()->redirect("login.php")`

`Auth`'s interface is unchanged. `requireLogin()`'s own internal redirect stays
inside `Auth` and is untouched. Whether add-comment, archive or create should
honour `$get["next"]` is a behaviour change and deliberately not part of this
decision; only the assign branch does today.

## Consequences

- Grep-verifiable: no bare `header("Location: ...")` remains outside
  `src/Auth.php`, mirroring ADR-0016's rule for the error/abort side.
- No new tests: the existing handler tests and the E2E suite assert the literal
  `Location:` header (including the `/logout.php` -> `login.php` pin), and
  `AuthTest` covers `redirect()` itself.
- A future scan must not re-propose an inline `header("Location: ...")` in a
  handler or front controller.
- Extends ADR-0013 and ADR-0016. Delivered by issue #267.
