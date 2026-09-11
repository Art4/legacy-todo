# ADR 0012: Page handlers declare explicit collaborators, assembled by Bootstrap factories

## Status

Accepted

## Context

ADR-0008 split the page layer into six per-page handlers, and ADR-0009 made Bootstrap the single
composition root. But by issue #248 the handlers' constructors still received the whole `Bootstrap
$app` locator and reached mid-flow into `$this->app->todos()`, `$this->app->csrf()`, and friends to
fetch their collaborators — roughly twenty such call sites across the six handlers. That kept the
handlers' real dependency sets invisible (a constructor said "everything" and tests had to stub the
locator), and it smuggled a service-locator pattern into a layer the ADRs had otherwise given
explicit seams.

## Decision

Each page handler's constructor now declares exactly the collaborators it works with, typed and
explicit:

- `AddTodoHandler(Auth, Todos, Taxonomy, Uploads, Csrf, Layout)`
- `TodoHandler(Auth, Todos, TodoActivity, Users, Csrf, Layout)`
- `LoginHandler(Auth, Users, Csrf, Layout, string $siteName)`
- `AdminHandler(Auth, Users, Taxonomy, Csrf, Layout, string $siteName)`
- `DeleteTodoHandler(Auth, Todos, Layout)`
- `EditTodoHandler(Auth, Todos, Csrf, Layout)`

Bootstrap gains one lazy factory method per handler (`addTodoHandler()`, `todoHandler()`,
`loginHandler()`, `adminHandler()`, `deleteTodoHandler()`, `editTodoHandler()`) that relays the
already-owned module accessors; the six front controllers shrink to `$app-><factory>()` +
`handle()`, and no handler holds or reaches for the whole `$app` locator any more (grep-verifiable:
no `$this->app`) (issue #248).

Alternatives rejected rather than litigated repeatedly:

- **Keep passing the whole `Bootstrap` locator** — the pre-#248 shape: dependency sets stay
  invisible, tests keep stubbing the locator, and a handler can blurp any module it likes rather
  than declaring what it needs.
- **A DI container** — already rejected at ADR-0009 for the module graph; nothing about the handler
  layer changes that calculus.

## Consequences

- A future scan must not re-propose passing the whole `Bootstrap` locator into a handler, or
  handler-side module construction: issue #248 is delivered by this MR, and
  explicit-collaborator constructors assembled by Bootstrap's handler factories are the settled
  shape of the handler seam (deepening ADR-0008/ADR-0009).
- A handler's constructor is its complete, explicit dependency inventory; adding a dependency
  ripples to its factory in Bootstrap and its unit test and nowhere else.
- `LoginHandler`/`AdminHandler` take the plain `string $siteName` where they need it directly —
  the one deliberate scalar alongside the module collaborators.