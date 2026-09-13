# ADR 0017: Every data-access module returns a typed read shape

## Status

Accepted

## Context

`Todos` established the rule "every read path returns the same immutable
value object" for one entity (`Todo`, hydrated only inside `Todos`). It was
never generalized: `TodoActivity` (`commentsForTodo`, `assignmentsForTodo`,
`findComment`), `Users` (`findById`, `findByUsername`, `authenticate`,
`listAll`), `Taxonomy` (`listCategories`, `listTags`), and `Auth::currentUser()`
kept returning raw `array<string,mixed>` rows. Callers decoded string keys in
their markup (`$c["body"]`, `$a["username"]`, `$u["id"]`,
`currentUser()["user_id"]`) — every data module stayed a shallow facade over
PDO whose interface was nearly as complex as its implementation, and the
key-decoding concentrated in the hottest callers (`TodoHandler::renderTodoDetail`,
`AdminHandler::renderAdmin`). A query that changed its column set broke at a
call site, not at the module (issue #266).

## Decision

Every data-access module hydrates its own read shape into an immutable value
object; no caller outside the owning module ever decodes a row's array key.
This generalizes the `Todo` precedent to the whole data layer:

- `TodoActivity` hydrates `Comment` (`commentsForTodo`/`findComment` — one
  shape across both paths, `findComment` now joins `users.username` to match)
  and `Assignment` (`assignmentsForTodo`).
- `Users` hydrates `User` (`findById`/`findByUsername`/`authenticate`/`listAll`);
  the password hash never leaves the module.
- `Auth::currentUser()` returns `AuthenticatedUser` — the session's own
  three-field identity (`user_id`/`username`/`role`), not a partial `User`
  with a fabricated empty email; `canDeleteComment(Comment $comment)` takes
  the typed comment instead of a raw array.
- `Taxonomy` hydrates `Category` (`listCategories`) and `Tag` (`listTags`).
  The batch helpers `categoryNamesForTodos()`/`tagsForTodos()` stay primitive
  maps — their only consumer is `Todos::hydrate`, never markup, so they sit
  outside this rule.

Write paths (`addComment`, `assign`, `create`, `register`, `createCategory`,
`createTag`, …) are unaffected — this rule is about reads.

## Consequences

- Grep-verifiable: no `array<string, mixed>` row key is read outside the
  owning data module (`Todos`, `TodoActivity`, `Users`, `Auth`, `Taxonomy`).
  Callers use accessors exclusively.
- Static analysis (PHPStan/Psalm) now type-checks every read seam, turning a
  column-rename break into a compile-time error instead of a runtime break at
  the hottest call site.
- A future scan must not re-propose this as a fresh candidate for any data
  module — the rule is now project-wide, not per-entity. A brand-new
  data-access module is expected to follow it from the start.
- New value objects live beside `Todo`/`RegistrationResult` in `src/`, one
  accessor per field, hydrated only inside the owning module; they are
  exercised through that module's own test suite, not separate test files.
