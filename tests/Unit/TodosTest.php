<?php

declare(strict_types=1);

use Art4\LegacyTodo\Todos;

final class TodosTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var Todos */
    private $todos;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)');
        $this->todos = new Todos($this->pdo);
    }

    private function seedTodo(array $row): int
    {
        $this->pdo->exec(
            "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES ("
            . $row["user_id"] . ",'" . $row["title"] . "','" . $row["text"] . "','" . $row["status"] . "',"
            . $row["priority"] . ",'" . $row["due_date"] . "'," . $row["archived"] . ",'2026-01-10')",
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function testListActiveReturnsUnscopedActiveTodosSortedByStatusThenDue(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Later open", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 2, "title" => "Earlier open", "text" => "", "status" => "open", "priority" => 3, "due_date" => "2026-06-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Done stays", "text" => "", "status" => "done", "priority" => 2, "due_date" => "2026-01-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 3, "title" => "Archived hidden", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 1]);

        $titles = array_column($this->todos->listActive(), "title");

        $this->assertSame(["Done stays", "Earlier open", "Later open"], $titles);
    }

    public function testListActiveBoltsTagsOntoEachRow(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Tagged", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);
        $this->pdo->exec("INSERT INTO todo_tags (todo_id, tag_id) VALUES (" . $id . ", 7)");

        $row = $this->todos->listActive()[0];

        $this->assertSame([["todo_id" => $id, "tag_id" => 7]], $row["tags"]);
    }

    public function testListFilteredAppliesStatusPriorityAndDueFilters(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Low done", "text" => "", "status" => "done", "priority" => 3, "due_date" => "2026-01-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 2, "title" => "High open", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-06-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 3, "title" => "Medium open late", "text" => "", "status" => "open", "priority" => 2, "due_date" => "2026-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 4, "title" => "Archived hidden", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 1]);

        $this->assertSame(
            ["Low done", "High open", "Medium open late"],
            array_column($this->todos->listFiltered("", "", ""), "title"),
        );
        $this->assertSame(["Low done"], array_column($this->todos->listFiltered("done", "", ""), "title"));
        $this->assertSame(["High open"], array_column($this->todos->listFiltered("", "1", ""), "title"));
        $this->assertSame(["Low done", "High open"], array_column($this->todos->listFiltered("", "", "2026-06-15"), "title"));
        $this->assertSame(["Medium open late"], array_column($this->todos->listFiltered("open", "2", ""), "title"));
    }

    public function testListFilteredBoltsCatAndTagsOntoEachRow(): void
    {
        $uncategorized = $this->seedTodo(["user_id" => 1, "title" => "No category", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);
        $this->pdo->exec('INSERT INTO categories (name,user_id) VALUES ("Arbeit",1)');
        $categorized = $this->seedTodo(["user_id" => 1, "title" => "Categorized", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-02", "archived" => 0]);
        $this->pdo->exec("UPDATE todos SET category_id=1 WHERE id=" . $categorized);
        $this->pdo->exec("INSERT INTO todo_tags (todo_id, tag_id) VALUES (" . $uncategorized . ", 9)");

        $rows = [];
        foreach ($this->todos->listFiltered("", "", "") as $row) {
            $rows[$row["id"]] = $row;
        }

        $this->assertSame("", $rows[$uncategorized]["cat"]);
        $this->assertSame("Arbeit", $rows[$categorized]["cat"]);
        $this->assertSame([["todo_id" => $uncategorized, "tag_id" => 9]], $rows[$uncategorized]["tags"]);
        $this->assertSame([], $rows[$categorized]["tags"]);
    }

    public function testSearchMatchesCaseInsensitiveOnTitleIgnoringArchived(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Erstes Todo", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 2, "title" => "noch was", "text" => "", "status" => "done", "priority" => 2, "due_date" => "2026-11-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 3, "title" => "Erstes TODO archiviert", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 1]);

        $this->assertSame(["Erstes Todo"], array_column($this->todos->search("ERSTES"), "title"));
        $this->assertSame(["noch was"], array_column($this->todos->search("NOCH"), "title"));
        $this->assertSame(["Erstes Todo"], array_column($this->todos->search("todo"), "title"));
    }

    public function testSearchRowsCarryNeitherCatNorTags(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Treffer", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);

        $row = $this->todos->search("tref")[0];

        $this->assertArrayNotHasKey("cat", $row);
        $this->assertArrayNotHasKey("tags", $row);
    }
}
