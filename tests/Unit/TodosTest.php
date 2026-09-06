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
}
