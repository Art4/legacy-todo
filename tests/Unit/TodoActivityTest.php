<?php

declare(strict_types=1);

use Art4\LegacyTodo\TodoActivity;

final class TodoActivityTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var TodoActivity */
    private $activity;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)');
        $this->activity = new TodoActivity($this->pdo);
    }

    private function seedUser(string $username): int
    {
        $this->pdo->exec("INSERT INTO users (username,password,role,email,created_at) VALUES ('" . $username . "','pw','user','" . $username . "@example.com','2026-01-01')");

        return (int) $this->pdo->lastInsertId();
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TodoActivity::class, $this->activity);
    }

    public function testCommentsForTodoReturnsCommentsWithAuthorUsernameInInsertionOrder(): void
    {
        $authorId = $this->seedUser("alice");
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (1," . $authorId . ",'Erster','2026-01-12 10:00:00')");
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (1," . $authorId . ",'Zweiter','2026-01-12 11:00:00')");

        $rows = $this->activity->commentsForTodo(1);

        $this->assertCount(2, $rows);
        $this->assertSame("alice", $rows[0]["username"]);
        $this->assertSame("Erster", $rows[0]["body"]);
        $this->assertSame("Zweiter", $rows[1]["body"]);
    }

    public function testCommentsForTodoIsEmptyForTodoWithoutComments(): void
    {
        $this->assertSame([], $this->activity->commentsForTodo(1));
    }

    public function testCommentsForTodoIsScopedToTodo(): void
    {
        $authorId = $this->seedUser("bob");
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (2," . $authorId . ",'Fremder','2026-01-12 09:00:00')");

        $this->assertSame([], $this->activity->commentsForTodo(1));
        $this->assertCount(1, $this->activity->commentsForTodo(2));
    }

    public function testAddCommentInsertsScopedToTodoWithBodyAuthorAndCreatedAt(): void
    {
        $authorId = $this->seedUser("carol");

        $result = $this->activity->addComment(3, $authorId, "Neuer Kommentar");

        $this->assertTrue($result);
        $row = $this->pdo->query("SELECT * FROM comments WHERE todo_id=3")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(3, $row["todo_id"]);
        $this->assertSame($authorId, $row["user_id"]);
        $this->assertSame("Neuer Kommentar", $row["body"]);
        $this->assertSame(date("Y-m-d H:i:s"), $row["created_at"]);
    }
}
