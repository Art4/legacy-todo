# ADR 0014: Layout owns the full page chrome; per-page titles flow through header()

## Status

Accepted

## Context

ADR-0008 split the page layer into per-page handlers, `Layout`, and `Csrf`, and recorded that
"rendering and escaping live in `Layout`". In practice, though, only `index.php` rendered through
`Layout`'s chrome — the six handlers (`AddTodoHandler`, `AdminHandler`, `DeleteTodoHandler`,
`EditTodoHandler`, `LoginHandler`, `TodoHandler`) each hand-rolled their own `<html>` scaffold,
inline `<head>`, and closing tags, and `index.php` itself still carried a second `<html>` opening
and explicit `</body></html>` after composing `Layout`. Issue #251 re-proposed that: the page layer's
choke point was supposed to be `Layout`'s `header()`/`footer()`, yet each page owned its own outer
markup, so a future layout change (a doctype tweak, a meta or stylesheet addition) would have to
touch eight files and risk divergence.

The pre-existing sketch: `Layout::header()` took no arguments (always the generic "site - site"
title), while the handlers' hand-rolled markup each bubbled a page-specific title alongside it.

## Decision

Deepen `Layout` into the one page-chrome seam every rendered page passes through:

- `Layout::header(?string $title = null)` — the page may pass a per-page title, escaped via the
  existing `text()` surface; no `$title` means the generic "site - site" title. `footer()` alone
  closes the scaffold.
- `Layout` now owns the single `<html>` scaffold, `<head>`, per-page `<title>`, and the closing
  `</body></html>`. No page — handler or front controller — owns any outer markup of its own.
- Every rendered page composes `header($title) . <content> . footer()`: the six handlers and
  `index.php`, which follow the same shape as the handlers (its DB-heavy Dashboard logic stays in
  `Dashboard`, so it keeps no handler of its own, per ADR-0008).
- The route-level tests (`testPageRendersWithinLayoutChrome`) pin each page to the seam; escaping
  of the per-page title is covered by `LayoutTest`.

## Consequences

- A future scan must not re-propose per-page chrome, moving the `<html>` scaffold back into
  handlers/front controllers, or re-adding a non-escaping title path: issue #251 is delivered by
  this ADR, and `Layout::header()`/`footer()` is the single owned seam for the outer page chrome.
- Grep-verifiable: exactly one `<html>` and one `</body></html>` in the codebase (inside
  `src/Layout.php`); `Layout::header()` has exactly one parameter, the escaped title.
- Behavior is unchanged per page (the E2E suite is the guard), except pages that previously
  rendered a generic title now render their real per-page title.
- A layout change is now one edit in `Layout` instead of eight edits across pages.