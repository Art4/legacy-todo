<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\AddTodoHandler;
use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Fakes\RunCliHelper;

final class AddTodoHandlerTest extends PHPUnit\Framework\TestCase
{
    /** @var array<string, mixed> */
    private $session;

    /** @var AddTodoHandler */
    private $handler;

    protected function setUp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)');
        $this->session = [];
        $app = new Bootstrap($pdo, $this->session, 'Legacy Todo');
        $this->session['csrf_token'] = $app->auth()->csrfToken();
        $this->handler = new AddTodoHandler($app);
    }

    public function testAddTodoRendersFormAndEscapesPostValues(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "title" => '<b>', "text" => 'a"b', "due_date" => '2026-01-01'], []);

        $this->assertStringContainsString('<h1>Todo erstellen</h1>', (string) $output);
        $this->assertStringContainsString("value='&lt;b&gt;'", (string) $output);
        $this->assertStringContainsString("<textarea name='text'>a&quot;b</textarea>", (string) $output);
        $this->assertStringContainsString("value='2026-01-01'", (string) $output);
    }

    public function testAddTodoSaveWithEmptyTitleRendersTitelErforderlich(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "", "text" => "", "due_date" => ""], []);

        $this->assertStringContainsString('Titel erforderlich', (string) $output);
        $this->assertStringContainsString('<p>Titel erforderlich</p>', (string) $output);
    }

    public function testAddTodoSaveSuccessRedirectsToIndex(): void
    {
        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\AddTodoHandler($app); echo $h->handle($_POST, $_FILES);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            [],
            ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "Neu", "text" => "Text", "priority" => "1", "due_date" => "2026-02-01"],
            '$pdo->exec("INSERT INTO categories (name,user_id) VALUES (\'Allgemein\',1)");',
        );

        $this->assertSame("Location: index.php", $output);
    }

    public function testAddTodoWithUploadStoresViaUploadsModuleAndRedirects(): void
    {
        $uploadsDir = sys_get_temp_dir() . '/legacy-todo-addtodo-uploads-' . bin2hex(random_bytes(4));
        mkdir($uploadsDir, 0777, true);
        $tmp = tempnam(sys_get_temp_dir(), 'legacy-todo-addtodo-up-');
        file_put_contents($tmp, 'file-body');

        try {
            $output = RunCliHelper::run(
                '$h = new \Art4\LegacyTodo\AddTodoHandler($app); echo $h->handle($_POST, $_FILES);',
                ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
                [],
                ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "Upload-Todo", "text" => "", "priority" => "1", "due_date" => "2026-02-01"],
                '$pdo->exec("INSERT INTO categories (name,user_id) VALUES (\'Allgemein\',1)");',
                $uploadsDir,
                ["upload" => ["name" => "upload.png", "tmp_name" => $tmp, "error" => UPLOAD_ERR_OK, "size" => 9]],
            );

            $this->assertSame("Location: index.php", $output);
            $files = glob($uploadsDir . '/*') ?: [];
            $this->assertCount(1, $files);
            $this->assertStringNotContainsString('upload.png', basename($files[0]));
            $this->assertSame('file-body', file_get_contents($files[0]));
        } finally {
            @unlink($tmp);
            foreach (glob($uploadsDir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($uploadsDir);
        }
    }
}