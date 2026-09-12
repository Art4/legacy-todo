<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\EditTodoHandler;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Layout;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Users;

final class EditTodoHandlerTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var EditTodoHandler */
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
        $this->handler = new EditTodoHandler($auth, $todos, new Csrf($auth, new Layout('Legacy Todo', $auth)), new Layout('Legacy Todo', $auth));
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

    public function testEditTodoRendersFormAndEscapesFields(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => '<b>T</b>', "text" => 'x"y', "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($id, [], []);

        $this->assertStringContainsString('<h1>Todo bearbeiten</h1>', $output);
        $this->assertStringContainsString("value='&lt;b&gt;T&lt;/b&gt;'", $output);
        $this->assertStringContainsString("<textarea name='text'>x&quot;y</textarea>", $output);
    }

    public function testEditTodoMarksPrioritySelectedWhenOne(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "T", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($id, [], []);

        $this->assertStringContainsString("<option selected value='1'>Hoch</option>", $output);
    }

    public function testEditTodoSaveWithEmptyTitleRendersTitelErforderlich(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Alt", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($id, [], ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "", "text" => "", "priority" => "1", "status" => "open"]);

        $this->assertStringContainsString('Titel erforderlich', $output);
    }

    public function testEditTodoDeniesWhenCannotManage(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\EditTodoHandler($app->auth(), $app->todos(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 9, "username" => "eve", "role" => "user", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1],
            ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "X", "text" => "", "priority" => "2", "status" => "open"],
            '$pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (9,\'eve\',\'\',\'user\',\'e@x.com\',\'2026-01-01\')");'
                . '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,5,\'Fremdes\',\'\',\'open\',0,\'2026-01-10\')");',
        );
        $this->assertSame("Keine Berechtigung", $output);
    }

    public function testEditTodoSaveSuccessRedirectsToTodo(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\EditTodoHandler($app->auth(), $app->todos(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1],
            ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "Neu", "text" => "", "priority" => "2", "status" => "open"],
            '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,1,\'Alt\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Location: todo.php?id=1", $output);
    }

    public function testEditTodoSaveWithEvilNextRedirectsToDefaultTarget(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\EditTodoHandler($app->auth(), $app->todos(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1, "next" => "https://evil.com"],
            ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "Neu", "text" => "", "priority" => "2", "status" => "open"],
            '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,1,\'Alt\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Location: todo.php?id=1", $output);
    }
}
