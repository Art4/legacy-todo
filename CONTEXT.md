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

**Bootstrap**:
The single module (`Art4\LegacyTodo\Bootstrap`) that owns how a page starts — `Bootstrap::start(array $config = [])` boots the session, timezone, and SQLite DB itself (connect, schema, seed, all idempotent on the default db_file anchored at the repo root, `dirname(__DIR__) . '/database.sqlite'`, deliberately outside the `public/` webroot), then returns the page's module instances via `auth()`, `todos()`, `users()`, `todoActivity()`, `taxonomy()` and `siteName()`. Pages are thin: they call `Bootstrap::start()`, run their request logic through the modules, and render.
_Avoid_: per-page `session_start()`, per-page `include_once` of `config.php` / `db.php` / `functions.php`, per-page module construction

**public/ webroot**:
The only directory Apache serves (`public/`, per ADR-0003). It holds the 8 front controllers (`index.php`, `todo.php`, `admin.php`, `addtodo.php`, `edittodo.php`, `deletetodo.php`, `login.php`, `logout.php`) and `uploads/` (still reachable at `/uploads/...`). Everything internal — `src/`, `config.php`, `db.php`, `functions.php`, `includes/`, `vendor/`, tooling configs, and the SQLite database — stays at the repo root, outside the docroot, so it 404s instead of being downloadable.
_Avoid_: serving the repo root as DocumentRoot; intra-docroot `.htaccess`-style protection as a substitute for the `public/` boundary

**Dashboard**:
The single module (`Art4\LegacyTodo\Dashboard`) that owns the index page's overview composition — it picks which list answers the request (search wins over the `status`/`priority`/`due` filters; otherwise filtered; otherwise the active list) via `overview(array $query)`, reads the total/open/done/overdue counts, and produces the CSV export body. Depends only on `Todos`; pages pass the request query and render.
_Avoid_: page-local filter precedence, per-page stats/list reads

**Owner**:
The User a Todo belongs to. The Owner or an Administrator may edit or archive a Todo; anyone else is refused permission.
_Avoid_: creator, assignee

**Users**:
The single data-access module (`Art4\LegacyTodo\Users`) that owns every read and mutation of a User's auth data — find by id or username, authenticate, register, create, list. Login state (user id, username, role) is written to the session by the page, not by the module.
_Avoid_: UserManager, per-page user SQL

**Auth**:
The single data-access module (`Art4\LegacyTodo\Auth`) that owns login state and permission decisions — session reads/writes, identity lookup, role checks, and redirect-on-denial. Delegates data lookups to `Users` / `Todos`; never touches SQL. Complements `Users` (which stays session-free per above).
_Avoid_: AuthManager, session keys written outside Auth

**TodoActivity**:
The single data-access module (`Art4\LegacyTodo\TodoActivity`) that owns every read and mutation of a Todo's comments and assignments — comments for a todo, add comment, remove comment, assignments for a todo, assign.
_Avoid_: per-page comment/assignment SQL

**Taxonomy**:
The single data-access module (`Art4\LegacyTodo\Taxonomy`) that owns every read and mutation of the Todo classification data — the `categories`, `tags`, and `todo_tags` tables: list categories, list tags, create category, create tag, category names for a batch of todos, tags for a batch of todos.
_Avoid_: per-page category/tag SQL