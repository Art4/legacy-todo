# Legacy Todo

A small PHP/SQLite to-do application. All todo data access lives behind one module, and all user/auth data access behind another.

## Language

**Todo / To-do**:
The core entity — a unit of work tracked by the app. A Todo has exactly one Owner, exactly one status (`open` or `done`), and is archived, never deleted, when it leaves the active view.
_Avoid_: task, item, note

**Todos**:
The single data-access module (`Art4\LegacyTodo\Todos`) that owns every read and mutation of a Todo's state and lifecycle — list, filter, search, find, create, update, archive, CSV export and dashboard counts. Receives the shared `\PDO` and `Taxonomy` as constructor collaborators (injected by Bootstrap, ADR-0009).
_Avoid_: TodoManager, per-page SQL

**archivieren**:
The lifecycle action that flags a Todo as archived instead of deleting it. Archived To-dos stay in history (they still appear in the CSV export) but disappear from the lists, search and the Dashboard.
_Avoid_: delete, löschen (as the storage operation)

**Bootstrap**:
The single module (`Art4\LegacyTodo\Bootstrap`) that owns how a page starts and assembles the entire module graph — `Bootstrap::start(array $config = [])` boots the session, timezone, and SQLite DB itself (connect, schema, seed, all idempotent on the default db_file anchored at the repo root, `dirname(__DIR__) . '/database.sqlite'`, deliberately outside the `public/` webroot), then hands out every other module as a lazy shared instance via `auth()`, `todos()`, `users()`, `dashboard()`, `todoActivity()`, `taxonomy()`, `layout()`, `csrf()` and `siteName()`. It is the single construction site (composition root) — no module constructs another outside it: `Auth` receives `Users` + `Todos`, `Todos` receives `Taxonomy`, `Layout` receives the site name + `Auth`, `Csrf` receives `Auth` + `Layout` (ADR-0009). Pages are thin: they call `Bootstrap::start()`, run their request logic through the modules, and render.
_Avoid_: per-page `session_start()`, per-page `include_once`, module construction anywhere but Bootstrap — the legacy root bootstrap files (`config.php` / `db.php` / `functions.php`) are retired (ADR-0006), there is nothing left to include

**PSR-4 autoloading**:
The class-resolution mechanism for the app's own source: `composer.json`'s `autoload.psr-4` maps `Art4\LegacyTodo\` → `src/`, and `src/Bootstrap.php` — the composition root, which every front controller requires — wires it in via `require_once __DIR__ . "/../vendor/autoload.php"` (ADR-0010). New code in `src/` gets autoloaded instead of being pulled in by a manual path `require`.
_Avoid_: per-file manual `require_once` for namespaced `src/` classes; re-introducing the parceled legacy includes (`config.php` / `db.php` / `functions.php`)

**public/ webroot**:
The only directory Apache serves (`public/`, per ADR-0003). It holds the 8 front controllers (`index.php`, `todo.php`, `admin.php`, `addtodo.php`, `edittodo.php`, `deletetodo.php`, `login.php`, `logout.php`) and `uploads/` (still reachable at `/uploads/...`). Everything internal — `src/`, `vendor/`, tooling configs, and the SQLite database — stays at the repo root, outside the docroot, so it 404s instead of being downloadable.
_Avoid_: serving the repo root as DocumentRoot; intra-docroot `.htaccess`-style protection as a substitute for the `public/` boundary

**Dashboard**:
The single module (`Art4\LegacyTodo\Dashboard`) that owns the index page's overview composition — it picks which list answers the request (search wins over the `status`/`priority`/`due` filters; otherwise filtered; otherwise the active list) via `overview(array $query)`, reads the total/open/done/overdue counts, and produces the CSV export body. Depends only on `Todos`; pages pass the request query and render.
_Avoid_: page-local filter precedence, per-page stats/list reads

**page handler**:
The class that owns one stateful front controller's request flow — one per page (`AddTodoHandler`, `AdminHandler`, `DeleteTodoHandler`, `EditTodoHandler`, `LoginHandler`, `TodoHandler`). It runs the page's auth checks, data mutations, redirects, and page-specific markup; the front controller is a thin `Bootstrap::start()` + `handle()` dispatch. `index.php` stays a Dashboard composition and `logout.php` a one-liner on Auth, so neither gets a handler (ADR-0008).
_Avoid_: a page-wide god-class, per-page request logic scattered outside the handler

**Layout**:
The single module (`Art4\LegacyTodo\Layout`) that owns the shared page chrome and the output-escaping surface — `header()`/`footer()` composition and `text()`/`attr()` escaping used by every page that renders markup. Constructed by Bootstrap with the site name and `Auth` (ADR-0009).
_Avoid_: page-scattered `htmlspecialchars`, inline `<html>` scaffolding duplicated per page

**Uploads**:
The single module (`Art4\LegacyTodo\Uploads`) that owns every write to the `public/uploads/` directory beneath the webroot. `store(array $file)` writes a single uploaded file under a server-generated random name (`bin2hex(random_bytes(16))`, preserving the checked extension) and returns the stored path; it rejects anything not in the whitelisted extensions (gif, jpeg, jpg, pdf, png, txt, webp) or without a usable tmp file. Never writes a client-supplied filename.
_Avoid_: page-local `move_uploaded_file`, client-chosen paths

**Owner**:
The User a Todo belongs to. The Owner or an Administrator may edit or archive a Todo; anyone else is refused permission.
_Avoid_: creator, assignee

**Users**:
The single data-access module (`Art4\LegacyTodo\Users`) that owns every read and mutation of a User's auth data — find by id or username, authenticate, register, create, list. Login state (user id, username, role) is written to the session by the page, not by the module.
_Avoid_: UserManager, per-page user SQL

**Auth**:
The single data-access module (`Art4\LegacyTodo\Auth`) that owns login state and permission decisions — session reads/writes, identity lookup, role checks, and redirect-on-denial. Delegates data lookups to the `Users` / `Todos` it receives as constructor collaborators (injected by Bootstrap, ADR-0009); never touches SQL and never holds the connection. Complements `Users` (which stays session-free per above).
_Avoid_: AuthManager, session keys written outside Auth

**TodoActivity**:
The single data-access module (`Art4\LegacyTodo\TodoActivity`) that owns every read and mutation of a Todo's comments and assignments — comments for a todo, add comment, remove comment, assignments for a todo, assign.
_Avoid_: per-page comment/assignment SQL

**Taxonomy**:
The single data-access module (`Art4\LegacyTodo\Taxonomy`) that owns every read and mutation of the Todo classification data — the `categories`, `tags`, and `todo_tags` tables: list categories, list tags, create category, create tag, category names for a batch of todos, tags for a batch of todos.
_Avoid_: per-page category/tag SQL

**CSRF token**:
The session-bound token that protects every state-changing POST form. `Auth` owns it (lazily generated with `bin2hex(random_bytes(32))`, stored in the session, verified with `hash_equals`) and exposes `csrfToken()`/`validateCsrfToken()`; `Csrf` turns it into the `_csrf_token` hidden form field (`field()`) and rejects any non-empty POST without a matching token (`guard()`) with HTTP 403 + `exit` (ADR-0007, ADR-0008). GET-based state changes (`deletetodo.php?confirm=1`, `del_comment`) stay outside this protection.
_Avoid_: per-handler token generation, client-side token storage

**End-to-end suite**:
The automated HTTP-level test suite under `tests/Functional/` that boots the real app and drives it with real HTTP requests — a built-in server serving the real `public/` docroot, a fresh seeded SQLite database selected via the `LEGACY_TODO_DB_FILE` env override, and real session cookies. It replaces the manual per-PR click-through as the guard against request-time regressions (the #194 class: something that loads fine under PSR-4 in unit tests yet fails at request time because Bootstrap's wiring doesn't reach it). The now-deleted `BootstrapRequireListTest` once policed the redundant require_once graph; with PSR-4 handling all class resolution, the E2E suite is the sole remaining guard for request-time reachability.
_Avoid_: manual per-PR click-through as the request-time regression guard; unit tests as proof of request-time reachability