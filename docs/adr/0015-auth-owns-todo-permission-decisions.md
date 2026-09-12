# ADR 0015: Auth owns the single todo-permission decision seam

## Status

Accepted

## Context

The domain rule "the Owner or an Administrator may manage a Todo; anyone else is refused
permission" was enforced at some call sites and not others. Edit and archive gated on
`Auth::canManage()`, but the Todo detail page let any logged-in user comment on or assign any
todo, and comment deletion re-implemented the owner-or-admin check inline
(`$comment["user_id"] == $uid || $role === "admin"`) instead of going through the Auth seam.
The permission decision had no single depth — it lived as ad hoc conditions glued to each
action, and each permission audit found a new ungated seat (the pattern already shipped one
call site at a time: `d1f9c6f` guarded editTodo, `e6cebed`/`#227` authorized del_comment).
Issue #257 re-proposed this as a single seam.

## Decision

`Auth` is the single module that decides who may do what to a Todo. The whole permission
surface:

- `canManage($todoId): bool` — unchanged decision: the Todo's Owner or an Administrator.
- `requireManage($todoId): void` — guard verb: same deny-response (`Keine Berechtigung` +
  exit) `canManage()` gates behind; absorbs the identical deny-blocks in
  `EditTodoHandler`/`DeleteTodoHandler`.
- `canDeleteComment(array $comment): bool` — comment author or Administrator; a decision
  distinct from the todo-manage rule; absorbs the inline check in `TodoHandler`.

`add_comment` and `assign` now gate on the same manage rule as edit and archive. This is a
deliberate, minimal **tightening**: previously any logged-in user could comment on or assign
any todo. Denial happens on submit, at the handler — the detail page still renders to every
logged-in user (viewing a todo is not managing it). `del_comment`'s quiet-skip behavior is
preserved, just decided by `Auth::canDeleteComment()`.

Grep-verifiable: no authorization condition (`== "admin"`, comment-author comparison, deny
+ exit) anywhere outside `src/Auth.php`. A handler either calls one of the Auth methods or
reads identity for a data write (`currentUser()["user_id"]`), never computes a permission
itself. `TodoActivity` stays a session-free PDO module — its method signatures do not change.

## Consequences

- A future scan must not re-propose permission checks inside handlers, or reopen
  comment/assign as ungated seats.
- The tightening ships under the refactor label in the same family the loop already
  delivered (edit guard `d1f9c6f`, comment-delete authorization `e6cebed`/`#227`); the E2E
  suite pins the denial journey (`testCommentAndAssignDeniedForNonManagerOnForeignTodo`).
- `Auth`'s permission surface is now three methods; its PHPMD public-method suppression
  (already in place) is unchanged.
- Issue #257 is delivered by this ADR; the pending structural candidate with the same number
  is fully consumed.