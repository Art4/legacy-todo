# ADR 0004: Page owns output escaping, header/footer rendering, and byte-for-byte behavior

## Status

Accepted

## Context

The page layer past Bootstrap still decided the request→response flow and rendered HTML inline,
untested (issue #189). That service is the source of the Psalm `TaintedHtml`/`TaintedTextWithQuotes`
baseline: unescaped `$_GET`/`$_POST` echoed straight into markup on `addtodo`, `deletetodo`, `index`,
`login`, and `todo`. The rendering partials `includes/header.php` and `footer.php` keyed off ambient
page globals (`$auth`, `$cfg`, `$site_name`) — a hidden contract no page declared — instead of the
module instances `Bootstrap::start()` returns.

## Decision

A new `Page` module (`Art4\LegacyTodo\Page`) owns how a page turns a request into an escaped HTML
response or redirect, the analogue of Dashboard-on-index applied to the remaining front controllers.
The controllers become thin `Bootstrap::start()` + `Page` dispatchers; `index.php` stays on Dashboard
for composition but routes its unescaped `$_GET["q"]` render through Page's escape surface.

- **Escaping is Page's alone.** No page scatters `htmlspecialchars`; a private escape/render surface
  owns the encoding, which is what drains the psalm taint baseline.
- **Header/footer render from module instances**, retiring the `includes/header.php` + `footer.php`
  ambient-global contract; those files are deleted.
- **Behavior is preserved byte-for-byte.** Each page's current output is reproduced exactly,
  including its quirky German messages and its unvalidated `$_GET["next"]` redirect semantics. The
  latter is deliberate and already covered by ADR-0001 — it is not re-litigated here.
- `Page` delegates: page-start to `Bootstrap`, permissions/session to `Auth`, data to the data
  modules, and index composition to `Dashboard`. It adds no SQL and no new session-key access.

## Consequences

- A future scan should not re-propose splitting escaping back into pages, nor re-introducing
  `includes/header.php`/`footer.php` with their ambient-global contract, nor hardening escaping
  beyond today's byte-for-byte output under the refactor label (that is a behavior change on the
  normal bug path, not a structural refactor).
- `Page` stays the single surfaced seam for request-deciding, validation, escaping, and
  header/footer rendering; no page owns those concerns directly.
