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
        $this->assertSame(3, (int) $row["todo_id"]);
        $this->assertSame($authorId, (int) $row["user_id"]);
        $this->assertSame("Neuer Kommentar", $row["body"]);
        $this->assertSame(date("Y-m-d H:i:s"), $row["created_at"]);
    }

    public function testAddCommentRejectsInjectedBody(): void
    {
        $authorId = $this->seedUser("helen");

        $result = $this->activity->addComment(6, $authorId, "'); DROP TABLE comments; --");

        $this->assertTrue($result);
        $row = $this->pdo->query("SELECT * FROM comments WHERE todo_id=6")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("'); DROP TABLE comments; --", $row["body"]);
    }

    public function testAssignmentsForTodoReturnsAssigneesWithUsernamesInInsertionOrder(): void
    {
        $assigneeId = $this->seedUser("dave");
        $this->pdo->exec("INSERT INTO assignments (todo_id,user_id,assigned_by) VALUES (1," . $assigneeId . ",2)");
        $this->pdo->exec("INSERT INTO assignments (todo_id,user_id,assigned_by) VALUES (1," . $assigneeId . ",3)");

        $rows = $this->activity->assignmentsForTodo(1);

        $this->assertCount(2, $rows);
        $this->assertSame("dave", $rows[0]["username"]);
        $this->assertSame(2, (int) $rows[0]["assigned_by"]);
        $this->assertSame(3, (int) $rows[1]["assigned_by"]);
    }

    public function testAssignmentsForTodoIsEmptyForTodoWithoutAssignments(): void
    {
        $this->assertSame([], $this->activity->assignmentsForTodo(1));
    }

    public function testAssignmentsForTodoIsScopedToTodo(): void
    {
        $assigneeId = $this->seedUser("erin");
        $this->pdo->exec("INSERT INTO assignments (todo_id,user_id,assigned_by) VALUES (2," . $assigneeId . ",4)");

        $this->assertSame([], $this->activity->assignmentsForTodo(1));
        $this->assertCount(1, $this->activity->assignmentsForTodo(2));
    }

    public function testAssignInsertsScopedToTodo(): void
    {
        $assigneeId = $this->seedUser("frank");

        $result = $this->activity->assign(4, $assigneeId, 5);

        $this->assertTrue($result);
        $row = $this->pdo->query("SELECT * FROM assignments WHERE todo_id=4")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(4, (int) $row["todo_id"]);
        $this->assertSame($assigneeId, (int) $row["user_id"]);
        $this->assertSame(5, (int) $row["assigned_by"]);
    }

    public function testFindCommentReturnsRowForExistingComment(): void
    {
        $authorId = $this->seedUser("alice");
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (1," . $authorId . ",'Find me','2026-03-01 10:00:00')");
        $commentId = (int) $this->pdo->lastInsertId();

        $result = $this->activity->findComment($commentId);

        $this->assertNotNull($result);
        $this->assertSame($commentId, (int) $result["id"]);
        $this->assertSame(1, (int) $result["todo_id"]);
        $this->assertSame($authorId, (int) $result["user_id"]);
        $this->assertSame("Find me", $result["body"]);
    }

    public function testFindCommentReturnsNullForMissingId(): void
    {
        $this->assertNull($this->activity->findComment(999));
    }

    public function testRemoveCommentDeletesByIdAndIsIdempotent(): void
    {
        $authorId = $this->seedUser("gina");
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (5," . $authorId . ",'Kommentar','2026-02-01 10:00:00')");
        $commentId = (int) $this->pdo->lastInsertId();

        $this->assertTrue($this->activity->removeComment($commentId));

        $row = $this->pdo->query("SELECT * FROM comments WHERE id=" . $commentId)->fetch(\PDO::FETCH_ASSOC);
        $this->assertFalse($row);

        $this->assertTrue($this->activity->removeComment($commentId));
        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM comments WHERE id=" . $commentId)->fetchColumn());
    }
}
