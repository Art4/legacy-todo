# ADR 0016: Layout owns the single error/abort response seam

## Status

Accepted

## Context

Denied and aborted requests bypassed the page seam. `Auth::requireRole()` and
`Auth::requireManage()` glued the decision to a bare deny-response (`echo`
"Keine Rechte"/"Keine Berechtigung" + `exit`, ADR-0015 kept that shape),
`TodoHandler`'s not-found echoed "Not found", and `Csrf::guard()` echoed
"CSRF token invalid" — each an unwrapped text line on an otherwise HTTP 200
response, skipping the page chrome ADR-0014 gave every other page and skipping
escaping entirely (the CSRF message renders attacker-influenced bytes
unescaped). Issue #259 re-proposed this as a single seam.

## Decision

`Layout` owns error/abort responses, extending ADR-0014's "Layout owns the
chrome" to the failure side of the page seam:

- `Layout::errorPage(int $status, string $message): string` sets the wire
  status via `http_response_code()`, then composes the escaped message inside
  the same `header()`/`footer()` chrome (`<title>` + `<h1>`). Every aborted
  request is a composed page; the raw text line is retired.

`Auth`'s guard verbs are decision-only — they render nothing and never exit:

- `requireRole($role): bool` — the role check; `false` means denied.
- `requireManage($todoId): bool` — `canManage()`; `false` means denied.
- `requireLogin()` is untouched: redirect-on-denial (issue #258's scope) stays
  exactly as it is.

The owning handler turns a `false` into the denial response through the seam:

- `AdminHandler` → `errorPage(403, "Keine Rechte")`
- `EditTodoHandler`/`DeleteTodoHandler` → `errorPage(403, "Keine Berechtigung")`
- `TodoHandler` not-found → `errorPage(404, "Not found")`
- `Csrf::guard()` → `errorPage(403, "CSRF token invalid")` + `exit`

Wire behavior changes deliberately: not-found is now **404** and denials are
now **403** (previously 200).

The front controllers are part of this seam, not above it:
`errorPage()`'s status only reaches the wire if no output precedes it. The
`public/*.php` controllers must not dereference request keys before handing
off — `(int) $_GET["id"]` with no `id` in the query string emits an
undefined-key diagnostic that, when PHP renders it, flushes the response as
HTTP 200 before `http_response_code()` runs, silently defeating the seam
(and doing so version-dependently). They now default missing ids
(`(int) ($_GET["id"] ?? "")`), so the abort status is deterministic on every
PHP version.

## Consequences

- Grep-verifiable: no bare `echo ...; exit;` abort/denial remains outside
  `src/Layout.php`; every abort is a composed `errorPage()`.
- The suites pin the seam: `LayoutTest`'s errorPage battery (status, chrome,
  escaping), the updated deny-path assertions in `CsrfTest`,
  `AdminHandlerTest`, `EditTodoHandlerTest`, `DeleteTodoHandlerTest`,
  `TodoHandlerTest`, and the E2E wire-status pins (including the entry-point
  list) for 404/403.
- A future scan must not re-propose deny-rendering inside `Auth` or the
  handlers, nor a raw-text abort; the composed error page is the single shape.
- Pre-existing, out of scope: `DeleteTodoHandler` renders `$t["title"]`
  unguarded when `find()` returns null (an Administrator without a valid id),
  producing a diagnostic page rather than an errorPage — unchanged by this
  decision.
- Issue #259 is delivered by this ADR and MR #261.