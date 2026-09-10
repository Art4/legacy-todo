# ADR 0009: Bootstrap is the composition root for the entire module graph

## Status

Accepted

## Context

By issue #217, module construction was scattered. The six page handlers each built their own
`Layout` and `Csrf` (the `DeleteTodoHandler` its own `Layout`), `public/index.php` built its own
`Layout`, `Auth` silently constructed its own `Users` and `Todos` (keeping a `$pdo` it otherwise
never used), and `Todos` silently constructed its own `Taxonomy`. Constructor signatures and
wiring were duplicated across eleven call sites, so a constructor change rippled through every
handler and its tests, and no single file answered "which modules exist and how do they connect".
ADR-0008 had already settled what the page-layer seams are; #217 settles how the modules those
seams created get assembled.

## Decision

Bootstrap (ADR-0002's composition root / module locator) becomes the single construction site
for the whole module graph. Every module receives its collaborators through constructors:

- `Auth(Users $users, Todos $todos, &$session = null)` — drops its self-built `Users`/`Todos`
  and the `$pdo` property.
- `Todos(\PDO $pdo, Taxonomy $taxonomy)` — drops its self-built `Taxonomy`.
- `Layout(string $siteName, Auth $auth)` — no longer takes the whole Bootstrap.
- `Csrf(Auth $auth, Layout $layout)` — unchanged; only its construction site moves.

Bootstrap hands each module out as a lazy shared instance through `layout()` and `csrf()` (new
accessors) alongside the existing `auth()`, `todos()`, `users()`, `dashboard()`, `todoActivity()`
and `taxonomy()`. The page handlers and `index.php` pull `layout`/`csrf` from
`$app->layout()` / `$app->csrf()`; nothing in `src/` or `public/` constructs `Layout`, `Csrf`,
`Auth`, `Todos` or `Taxonomy` except Bootstrap (grep-verifiable).

Alternatives rejected rather than litigated repeatedly:

- **A DI container** — a fixed, five-module graph doesn't need a framework's indirection, and the
  decoupling it buys is the one construction site Bootstrap already is.
- **Handler-controller-side construction** — the pre-#217 shape's cure: it leaves construction
  scattered at the callers, so a parameter change still ripples through every handler.
- **Module self-construction** — resumes the pre-#217 state where a module carries a dependency
  it doesn't need (Auth holding `$pdo`) merely to build a sibling's helper.

## Consequences

- A future scan must not re-propose a DI container, or moving construction back out of Bootstrap
  into handlers or individual modules: issue #217 is delivered by this MR, and Bootstrap-as-
  composition-root is the settled assembly shape.
- A module's constructor is now the complete, explicit inventory of its collaborators; changing a
  signature ripples to Bootstrap's accessor (and that module's tests) and nowhere else.
- `Bootstrap::layout()`/`csrf()` join `auth()` etc. in the module-locator concern; the existing
  `Bootstrap.php` suppression in `phpstan.neon` covers the whole locator, not just the data modules.