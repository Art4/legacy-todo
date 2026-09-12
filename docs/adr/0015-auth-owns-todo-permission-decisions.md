# ADR 0015: Auth owns the single todo-permission decision seam

## Status

Accepted

## Context

Permission decisions for a Todo lived in a mix of shapes. Edit and archive gated
on `Auth::canManage()`, comment deletion re-implemented the owner-or-admin check
inline (`$comment["user_id"] == $uid || $role === "admin"`) instead of going
through the Auth seam, and each permission audit found a new ad hoc condition
glued to an action (the pattern shipped one call site at a time: `d1f9c6f`
guarded editTodo, `e6cebed`/`#227` authorized del_comment). Issue #257 re-proposed
this as a single seam.

## Decision

`Auth` is the single module that decides who may do what to a Todo. The whole
permission surface:

- `canManage($todoId): bool` — the Todo's Owner or an Administrator. Unchanged
  decision; edit and archive gate behind it.
- `requireManage($todoId): void` — guard verb: same deny-response (`Keine
  Berechtigung` + exit) `canManage()` gates behind; absorbs the identical
  deny-blocks in `EditTodoHandler`/`DeleteTodoHandler`.
- `canDeleteComment(array $comment): bool` — comment author or Administrator; a
  decision distinct from the todo-manage rule; absorbs the inline check in
  `TodoHandler`.

Commenting on a todo (`add_comment`) and assigning a todo (`assign`) are
**deliberately open collaboration actions**: every authenticated user may do
either, regardless of ownership — those two never call `canManage()` or
`requireManage()`. This is a documented decision, not an ungated seat: editing,
archiving, and comment deletion are ownership-gated, while commenting and
assigning are the collaboration surface anyone logged in may use. The README's
documented restriction ("Nur der Eigentümer oder ein Administrator darf es
bearbeiten") scopes to editing, matching this split. Denial happens on submit,
at the handler, for the gated actions; the detail page renders to every
logged-in user (viewing a todo is not managing it). `del_comment`'s quiet-skip
behavior is preserved, just decided by `Auth::canDeleteComment()`.

Grep-verifiable: no authorization condition (`== "admin"`, comment-author
comparison, deny + exit) for the gated actions anywhere outside `src/Auth.php`.
A handler either calls one of the Auth methods or reads identity for a data
write (`currentUser()["user_id"]` for `addComment`/`assign`/`create`), never
computes a permission itself. `TodoActivity` stays a session-free PDO module —
its method signatures do not change.

## Consequences

- A future scan must not re-propose permission checks inside handlers, and must
  not flag `add_comment`/`assign` as ungated seats — they are the deliberate
  open collaboration surface, documented here.
- The suites pin both sides: the gated actions deny non-managers
  (`testEditTodoDeniesWhenCannotManage`/`testDeleteTodoDeniesWhenCannotManage`,
  plus the `AuthTest` `requireManage` battery), and the open actions succeed for
  non-managers (`testTodoAddCommentAllowedForNonManagerOnForeignTodo`,
  `testTodoAssignAllowedForNonManagerOnForeignTodo`, and the E2E
  `testCommentAndAssignAllowedForNonManagerOnForeignTodo`).
- `Auth`'s permission surface is now three methods; its PHPMD public-method
  suppression (already in place) is unchanged.
- Issue #257 is delivered by this ADR; the pending structural candidate with the
  same number is fully consumed.