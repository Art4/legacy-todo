# Legacy Todo

A small PHP/SQLite to-do application. All todo data access lives behind one module, and all user/auth data access behind another.

## Language

**Todo / To-do**:
The core entity — a unit of work tracked by the app. A Todo has exactly one Owner, exactly one status (`open` or `done`), and is archived, never deleted, when it leaves the active view.
_Avoid_: task, item, note

**Todos**:
The single data-access module (`Art4\LegacyTodo\Todos`) that owns every read and mutation of a Todo's state and lifecycle — list, filter, search, find, create, update, archive, CSV export and dashboard counts.
_Avoid_: TodoManager, per-page SQL

**archivieren**:
The lifecycle action that flags a Todo as archived instead of deleting it. Archived To-dos stay in history (they still appear in the CSV export) but disappear from the lists, search and the Dashboard.
_Avoid_: delete, löschen (as the storage operation)

**Dashboard**:
The index page's overview of the active To-dos: total/open/done/overdue counts plus the active list, sorted open-first and then by due date.
_Avoid_: —

**Owner**:
The User a Todo belongs to. The Owner or an Administrator may edit or archive a Todo; anyone else is refused permission.
_Avoid_: creator, assignee

**Users**:
The single data-access module (`Art4\LegacyTodo\Users`) that owns every read and mutation of a User's auth data — find by id or username, authenticate, register, create, list. Login state (user id, username, role) is written to the session by the page, not by the module.
_Avoid_: UserManager, per-page user SQL

**TodoActivity**:
The single data-access module (`Art4\LegacyTodo\TodoActivity`) that owns every read and mutation of a Todo's comments and assignments — comments for a todo, add comment, remove comment, assignments for a todo, assign.
_Avoid_: per-page comment/assignment SQL