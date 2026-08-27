<?php
/**
 * Seam test for TodoRepository (.scratch/refactor-issues/03-structural-todo-persistence.md).
 *
 * No test framework is fulfilled in this repo's tooling tree yet (no PHPUnit
 * node reached), so this is a plain assert()-based script run directly with
 * the PHP 5.6 CLI inside the project's own Docker container:
 *
 *   ./run.sh exec php tests/todo_repository_test.php
 *
 * It talks to TodoRepository only — not through any of the five PHP entry
 * scripts — so it never touches session_start()/header()/HTML.
 */

require_once __DIR__ . '/../TodoRepository.php';

assert_options(ASSERT_BAIL, true);

function make_test_db()
{
    $db = new PDO('sqlite::memory:');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)");
    return $db;
}

// create() rejects an empty title
$db = make_test_db();
$repo = new TodoRepository($db);
assert($repo->create("", "text", 2, "2026-12-31", 1) === false);

// create() + findById() round-trip
$db = make_test_db();
$repo = new TodoRepository($db);
$ok = $repo->create("Wash the car", "before Sunday", 2, "2026-12-31", 1);
assert($ok === true);
$row = $repo->findById(1);
assert($row["title"] === "Wash the car");
assert($row["status"] === "open");
assert((int)$row["archived"] === 0);

// update() changes title/text/priority/status
$repo->update(1, "Wash the car twice", "before Sunday, again", 1, "done");
$row = $repo->findById(1);
assert($row["title"] === "Wash the car twice");
assert($row["status"] === "done");

// archive() flips the archived flag, findById() still returns it
// (archiving is a soft-delete per README: "Beim Löschen wird das To-do
// archiviert statt physisch gelöscht")
$repo->archive(1);
$row = $repo->findById(1);
assert((int)$row["archived"] === 1);
assert($row !== null);

echo "TodoRepository seam tests: all assertions passed.\n";
