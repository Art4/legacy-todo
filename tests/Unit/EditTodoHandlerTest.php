<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\EditTodoHandler;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Installer;
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
        (new Installer($this->pdo))->createSchema();
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

    public function testPageRendersWithinLayoutChrome(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "T", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle($id, [], []);

        $this->assertStringContainsString('<div class="header">', $output);
        $this->assertStringContainsString('<div class="footer">', $output);
        $this->assertStringNotContainsString('<html><body>', $output);
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

    public function testEditTodoSaveStorageFailureRendersFailureMessageWithoutRedirecting(): void
    {
        $output = RunCliHelper::run(
            ''
                . '$failingPdo = new \PDO("sqlite::memory:");'
                . '$failingPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);'
                . '(new \Art4\LegacyTodo\Installer($failingPdo))->createSchema();'
                . '$failingPdo->exec("INSERT INTO todos (id,user_id,title,text,status,priority,due_date,archived,created_at) VALUES (1,1,\'Alt\',\'\',\'open\',1,\'2026-01-01\',0,\'2026-01-10\')");'
                . '$failingPdo->exec("PRAGMA query_only = 1");'
                . '$failingTodos = new \Art4\LegacyTodo\Todos($failingPdo, new \Art4\LegacyTodo\Taxonomy($failingPdo));'
                . '$h = new \Art4\LegacyTodo\EditTodoHandler($app->auth(), $failingTodos, $app->csrf(), $app->layout());'
                . 'echo $h->handle((int) $get["id"], $get, $_POST);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            ["id" => 1],
            ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "Neu", "text" => "", "priority" => "2", "status" => "open"],
            '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,1,\'Alt\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertStringNotContainsString('Location:', $output);
        $this->assertStringContainsString('Speichern fehlgeschlagen', $output);
        $this->assertStringContainsString('<h1>Todo bearbeiten</h1>', $output);
    }

    public function testEditTodoDeniesWhenCannotManage(): void
    {
        $id = $this->seedTodo(["user_id" => 5, "title" => "Fremdes", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 9;
        $this->session['username'] = 'eve';
        $this->session['role'] = 'user';

        $output = $this->handler->handle($id, [], []);

        $this->assertStringContainsString("Keine Berechtigung", $output);
        $this->assertStringContainsString("<title>Keine Berechtigung</title>", $output);
        $this->assertStringContainsString("</body></html>", $output);
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
