# ADR 0008: Page split into page handlers, Layout, and Csrf at three seams

## Status

Accepted

## Context

ADR-0004 left the page layer as a single `Page` module, and by issue #209 that module had
grown into a 476-line god-class: it owned the request flow, permission checks, redirects, the
CSRF form-field/guard contract, and inline HTML rendering for six front controllers at once.
Every endpoint method was a flat sequence of auth-check, data-mutation, redirect, and
string-concatenated markup, so every feature touching a front controller changed the one class —
concentrating merge conflicts and suppressing two design-quality rules (PHPMD
`TooManyPublicMethods` and `ExcessiveClassComplexity`) in `phpstan.neon`.

## Decision

Delete `Page` and split it along three natural seams (issue #209):

- **Each front controller owns its flow.** Six per-page handlers — `AddTodoHandler`,
  `AdminHandler`, `DeleteTodoHandler`, `EditTodoHandler`, `LoginHandler`, `TodoHandler` — each own
  one stateful page's request flow, permission checks, redirects, and page-specific markup; the
  front controller becomes a thin `Bootstrap::start()` + `echo $handler->handle(...)` dispatch.
  `index.php` stays a Dashboard composition and `logout.php` a one-liner on `Auth`, so neither gets
  a handler.
- **Rendering and escaping live in `Layout`.** The new `Layout` module owns the shared chrome
  (`header()`/`footer()`) and the output-escaping surface (`text()`/`attr()`); every page renders
  and escapes through it, never scattering `htmlspecialchars`.
- **The POST-protection contract lives in `Csrf`.** The new `Csrf` module wraps `Auth`'s
  session-bound token into the `_csrf_token` hidden form field (`field()`) and enforces
  `guard()` — 403 + `exit` for any non-empty POST without a matching token — per ADR-0007.

Behavior is preserved byte-for-byte per page (the E2E suite is the guard), including the GET-based
state changes that stay outside CSRF protection (ADR-0007) and the unvalidated redirects (ADR-0001).

## Consequences

- A future scan must not re-propose splitting, merging, or relocating the seams created here:
  issue #209 is delivered by this MR, and the per-handler / Layout / Csrf split is the settled
  shape of the page layer.
- Escaping and page chrome stay in `Layout`; a scan should not propose moving escaping back into
  handlers or reassembling a page-wide god-class.
- The POST-protection contract stays in `Csrf`; a scan should not propose relocating token
  generation, the hidden field, or the request-time guard.
- The `src/Page.php` entry suppressed in `phpstan.neon` dies with the class; the remaining
  `Bootstrap.php` suppression is Bootstrap's own module-locator concern, not the page layer's.