<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\DeleteTodoHandler;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Layout;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Users;

final class DeleteTodoHandlerTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var DeleteTodoHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)');
        $this->session = [];
        $users = new Users($this->pdo);
        $taxonomy = new Taxonomy($this->pdo);
        $todos = new Todos($this->pdo, $taxonomy);
        $auth = new Auth($users, $todos, $this->session);
        $this->session['csrf_token'] = $auth->csrfToken();
        $this->handler = new DeleteTodoHandler($auth, $todos, new Layout('Legacy Todo', $auth));
    }

    private function seedTodo(array $row): int
    {
        $this->pdo->exec(
            "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES ("
            . $row["user_id"] . ",'" . $row["title"] . "','" . ($row["text"] ?? "") . "','" . $row["status"] . "',"
            . $row["priority"] . ",'" . $row["due_date"] . "'," . ($row["archived"] ?? 0) . ",'2026-01-10')",
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function testPageRendersWithinLayoutChrome(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Wichtig", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($id, []);

        $this->assertStringContainsString('<div class="header">', $output);
        $this->assertStringContainsString('<div class="footer">', $output);
        $this->assertStringNotContainsString('<html><body>', $output);
    }

    public function testDeleteTodoRendersConfirmPageAndEscapesTitleAndId(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => '<b>Wichtig</b>', "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($id, []);

        $this->assertStringContainsString('<h1>Löschen?</h1>', $output);
        $this->assertStringContainsString('<p>&lt;b&gt;Wichtig&lt;/b&gt; wirklich archivieren?</p>', $output);
        $this->assertStringContainsString('href="deletetodo.php?id=' . $id . '&confirm=1"', $output);
    }

    public function testDeleteTodoConfirmArchivesAndRedirectsToIndex(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\DeleteTodoHandler($app->auth(), $app->todos(), $app->layout()); echo $h->handle((int) $get["id"], $get);',
            ["user_id" => 1, "username" => "alice", "role" => "admin"],
            ["id" => 1, "confirm" => "1"],
            [],
            '$pdo->exec("INSERT INTO todos (user_id,title,text,status,archived,created_at) VALUES (1,\'Wichtig\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Location: index.php", $output);
    }

    public function testDeleteTodoDeniesWhenCannotManage(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\DeleteTodoHandler($app->auth(), $app->todos(), $app->layout()); echo $h->handle((int) $get["id"], $get);',
            ["user_id" => 9, "username" => "eve", "role" => "user"],
            ["id" => 1],
            [],
            '$pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (9,\'eve\',\'\',\'user\',\'e@x.com\',\'2026-01-01\')");'
                . '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,5,\'Fremdes\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertStringContainsString("Keine Berechtigung", $output);
        $this->assertStringContainsString("<title>Keine Berechtigung</title>", $output);
        $this->assertStringContainsString("</body></html>", $output);
    }
}
