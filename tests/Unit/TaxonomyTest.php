<?php

declare(strict_types=1);

use Art4\LegacyTodo\Taxonomy;

final class CountingStatement extends \PDOStatement
{
    public static int $count = 0;

    protected function __construct()
    {
        self::$count++;
    }
}

final class TaxonomyTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var Taxonomy */
    private $taxonomy;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)');
        $this->taxonomy = new Taxonomy($this->pdo);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Taxonomy::class, $this->taxonomy);
    }

    public function testCreateCategoryInsertsWithNameAndOwnerUserId(): void
    {
        $this->assertTrue($this->taxonomy->createCategory("Arbeit", 3));

        $row = $this->pdo->query("SELECT * FROM categories")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("Arbeit", $row["name"]);
        $this->assertSame(3, $row["user_id"]);
    }

    public function testCreateCategoryRejectsEmptyName(): void
    {
        $this->assertFalse($this->taxonomy->createCategory("", 3));

        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn());
    }

    public function testCreateTagInserts(): void
    {
        $this->assertTrue($this->taxonomy->createTag("wichtig"));

        $row = $this->pdo->query("SELECT * FROM tags")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("wichtig", $row["name"]);
    }

    public function testCreateTagRejectsEmptyName(): void
    {
        $this->assertFalse($this->taxonomy->createTag(""));

        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn());
    }

    public function testListCategoriesReturnsAllRowsInInsertionOrder(): void
    {
        $this->taxonomy->createCategory("Privat", 1);
        $this->taxonomy->createCategory("Arbeit", 2);

        $rows = $this->taxonomy->listCategories();

        $this->assertCount(2, $rows);
        $this->assertSame("Privat", $rows[0]["name"]);
        $this->assertSame(1, $rows[0]["user_id"]);
        $this->assertSame("Arbeit", $rows[1]["name"]);
    }

    public function testListTagsReturnsAllRowsInInsertionOrder(): void
    {
        $this->taxonomy->createTag("wichtig");
        $this->taxonomy->createTag("dringend");

        $rows = $this->taxonomy->listTags();

        $this->assertCount(2, $rows);
        $this->assertSame("wichtig", $rows[0]["name"]);
        $this->assertSame("dringend", $rows[1]["name"]);
    }

    public function testCategoryNamesForTodosMapsEachTodoToItsCategoryNameInOneQuery(): void
    {
        $this->taxonomy->createCategory("Arbeit", 1);
        $workCat = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'A','open',1,'2026-01-01',0,'2026-01-01')");
        $todoA = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'B','open',1,'2026-01-02',0,'2026-01-01')");
        $todoB = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("UPDATE todos SET category_id=" . $workCat . " WHERE id=" . $todoA);

        $names = $this->taxonomy->categoryNamesForTodos([$todoA, $todoB]);

        $this->assertSame("Arbeit", $names[$todoA]);
        $this->assertSame("", $names[$todoB]);
    }

    public function testTagsForTodosMapsTodosToTheirTagLinksInOneQuery(): void
    {
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'A','open',1,'2026-01-01',0,'2026-01-01')");
        $todoA = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'B','open',1,'2026-01-02',0,'2026-01-01')");
        $todoB = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todo_tags (todo_id,tag_id) VALUES (" . $todoA . ",7)");
        $this->pdo->exec("INSERT INTO todo_tags (todo_id,tag_id) VALUES (" . $todoA . ",8)");

        $tags = $this->taxonomy->tagsForTodos([$todoA, $todoB]);

        $this->assertSame([["todo_id" => $todoA, "tag_id" => 7], ["todo_id" => $todoA, "tag_id" => 8]], $tags[$todoA]);
        $this->assertSame([], $tags[$todoB]);
    }

    public function testTagsForTodosUsesOneStatementForABatch(): void
    {
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'A','open',1,'2026-01-01',0,'2026-01-01')");
        $todoA = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'B','open',1,'2026-01-02',0,'2026-01-01')");
        $todoB = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todo_tags (todo_id,tag_id) VALUES (" . $todoA . ",7)");
        $this->pdo->exec("INSERT INTO todo_tags (todo_id,tag_id) VALUES (" . $todoB . ",8)");

        $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [CountingStatement::class]);
        CountingStatement::$count = 0;

        $this->taxonomy->tagsForTodos([$todoA, $todoB]);

        $this->assertSame(1, CountingStatement::$count);
    }

    public function testCategoryNamesForTodosUsesOneStatementForABatch(): void
    {
        $this->taxonomy->createCategory("Arbeit", 1);
        $workCat = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'A','open',1,'2026-01-01',0,'2026-01-01')");
        $todoA = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO todos (user_id,title,status,priority,due_date,archived,created_at) VALUES (1,'B','open',1,'2026-01-02',0,'2026-01-01')");
        $todoB = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("UPDATE todos SET category_id=" . $workCat . " WHERE id=" . $todoA);

        $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [CountingStatement::class]);
        CountingStatement::$count = 0;

        $this->taxonomy->categoryNamesForTodos([$todoA, $todoB]);

        $this->assertSame(1, CountingStatement::$count);
    }

    public function testCategoryNamesForTodosReturnsEmptyMapForEmptyBatch(): void
    {
        $this->assertSame([], $this->taxonomy->categoryNamesForTodos([]));
    }

    public function testTagsForTodosReturnsEmptyMapForEmptyBatch(): void
    {
        $this->assertSame([], $this->taxonomy->tagsForTodos([]));
    }
}
