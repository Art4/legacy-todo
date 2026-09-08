<?php

declare(strict_types=1);

use Art4\LegacyTodo\Dashboard;
use Art4\LegacyTodo\Todos;

final class DashboardTest extends PHPUnit\Framework\TestCase
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

    private function dashboard(): Dashboard
    {
        return new Dashboard($this->todos);
    }

    public function testOverviewWithoutFiltersReturnsActiveTodosAndStats(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Offen früh", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2025-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Offen spät", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-06-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Erledigt", "text" => "", "status" => "done", "priority" => 1, "due_date" => "2026-01-02", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Archiviert", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2025-01-01", "archived" => 1]);

        $overview = $this->dashboard()->overview([]);

        $this->assertSame(["todos", "stats"], array_keys($overview));
        $this->assertSame(["Erledigt", "Offen früh", "Offen spät"], array_column($overview["todos"], "title"));
        $this->assertSame(["c" => 3, "open" => 2, "done" => 1, "overdue" => 1], $overview["stats"]);
    }
}