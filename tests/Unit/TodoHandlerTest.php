<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Layout;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\TodoActivity;
use Art4\LegacyTodo\TodoHandler;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Users;

final class TodoHandlerTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var TodoHandler */
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
        $layout = new Layout('Legacy Todo', $auth);
        $this->handler = new TodoHandler($auth, $todos, new TodoActivity($this->pdo), $users, new Csrf($auth, $layout), $layout);
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
        $tid = $this->seedTodo(["user_id" => 1, "title" => '<b>Titel</b>', "text" => "Body", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($tid, [], []);

        $this->assertStringContainsString('<title>Todo - &lt;b&gt;Titel&lt;/b&gt;</title>', (string) $output);
        $this->assertStringContainsString('<div class="header">', (string) $output);
        $this->assertStringContainsString('<div class="footer">', (string) $output);
        $this->assertStringNotContainsString('<html><body>', (string) $output);
    }

    public function testTodoDetailRendersAndEscapesFields(): void
    {
        $this->pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (2,'carol','','user','c@x.com','2026-01-01')");
        $tid = $this->seedTodo(["user_id" => 1, "title" => '<b>Titel</b>', "text" => 'Body & "quotes"', "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (" . $tid . ",2,'<script>x</script>','2026-01-12')");
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($tid, [], []);

        $this->assertStringContainsString('<h1>&lt;b&gt;Titel&lt;/b&gt;</h1>', (string) $output);
        $this->assertStringContainsString('<p>Body &amp; &quot;quotes&quot;</p>', (string) $output);
        $this->assertStringContainsString("<p>&lt;script&gt;x&lt;/script&gt; - User 2 <a href='todo.php?id=" . $tid . "&del_comment=", (string) $output);
        $this->assertStringContainsString('<small>carol</small>', (string) $output);
        $this->assertStringContainsString("<option value='2'>carol</option>", (string) $output);
        $this->assertStringContainsString('name="_csrf_token"', (string) $output);
    }

    public function testTodoAddCommentRedirectsToTodo(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\TodoHandler($app->auth(), $app->todos(), $app->todoActivity(), $app->users(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1],
            ["_csrf_token" => $this->session['csrf_token'], "add_comment" => "Kommentieren", "body" => "Neuer Kommentar"],
            '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,1,\'Titel\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Location: todo.php?id=1", $output);
    }

    public function testTodoRemoveCommentDeletesItFromRender(): void
    {
        $this->pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (2,'carol','','user','c@x.com','2026-01-01')");
        $tid = $this->seedTodo(["user_id" => 1, "title" => "Titel", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->pdo->exec("INSERT INTO comments (id,todo_id,user_id,body,created_at) VALUES (77," . $tid . ",1,'weg damit','2026-01-12')");
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'user';

        $output = $this->handler->handle($tid, ["del_comment" => 77], []);

        $this->assertStringNotContainsString('weg damit', (string) $output);
        $this->assertStringNotContainsString('<h3>Kommentare</h3>', (string) $output);
    }

    public function testTodoRemoveCommentRefusedForNonOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (2,'carol','','user','c@x.com','2026-01-01')");
        $tid = $this->seedTodo(["user_id" => 1, "title" => "Titel", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->pdo->exec("INSERT INTO comments (id,todo_id,user_id,body,created_at) VALUES (88," . $tid . ",2,'fremder kommentar','2026-01-12')");
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'user';

        $output = $this->handler->handle($tid, ["del_comment" => 88], []);

        $this->assertStringContainsString('fremder kommentar', (string) $output);
    }

    public function testTodoAddCommentDeniedWhenCannotManage(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\TodoHandler($app->auth(), $app->todos(), $app->todoActivity(), $app->users(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 9, "username" => "eve", "role" => "user", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1],
            ["_csrf_token" => $this->session['csrf_token'], "add_comment" => "Kommentieren", "body" => "Unbefugter Kommentar"],
            '$pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (9,\'eve\',\'\',\'user\',\'e@x.com\',\'2026-01-01\')");'
                . '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,5,\'Fremdes\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Keine Berechtigung", $output);
    }

    public function testTodoNotFoundPrintsNotFound(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\TodoHandler($app->auth(), $app->todos(), $app->todoActivity(), $app->users(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 1, "username" => "alice", "role" => "admin"],
            ["id" => 9999],
            [],
        );

        $this->assertSame("Not found", $output);
    }

    public function testTodoAssignWithEvilNextRedirectsToDefaultTarget(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\TodoHandler($app->auth(), $app->todos(), $app->todoActivity(), $app->users(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1, "next" => "https://evil.com"],
            ["_csrf_token" => $this->session['csrf_token'], "assign" => "Zuweisen", "assignee" => "alice"],
            '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,1,\'Titel\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Location: todo.php?id=1", $output);
    }

    public function testTodoAssignWithSafeNextHonoursTarget(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\TodoHandler($app->auth(), $app->todos(), $app->todoActivity(), $app->users(), $app->csrf(), $app->layout()); echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1, "next" => "index.php"],
            ["_csrf_token" => $this->session['csrf_token'], "assign" => "Zuweisen", "assignee" => "alice"],
            '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,1,\'Titel\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Location: index.php", $output);
    }
}
