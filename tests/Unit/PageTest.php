<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Page;

final class PageTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var Page */
    private $page;

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
        $app = new Bootstrap($this->pdo, $this->session, 'Legacy Todo');
        $this->page = new Page($app);
        $this->session['csrf_token'] = $app->auth()->csrfToken();
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

    public function testTodoRendersDetailAndEscapesFields(): void
    {
        $this->pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (2,'carol','','user','c@x.com','2026-01-01')");
        $tid = $this->seedTodo(["user_id" => 1, "title" => '<b>Titel</b>', "text" => 'Body & "quotes"', "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (" . $tid . ",2,'<script>x</script>','2026-01-12')");
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->page->todo($tid, [], [], []);

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
            'echo $page->todo((int) $get["id"], $get, $_POST, []);',
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
        $this->pdo->exec("INSERT INTO comments (id,todo_id,user_id,body,created_at) VALUES (77," . $tid . ",2,'weg damit','2026-01-12')");
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->page->todo($tid, ["del_comment" => 77], [], []);

        $this->assertStringNotContainsString('weg damit', (string) $output);
        $this->assertStringNotContainsString('<h3>Kommentare</h3>', (string) $output);
    }

    public function testTodoNotFoundPrintsNotFound(): void
    {
        $output = RunCliHelper::run(
            'echo $page->todo(9999, $get, $_POST, []);',
            ["user_id" => 1, "username" => "alice", "role" => "admin"],
            ["id" => 9999],
            [],
        );

        $this->assertSame("Not found", $output);
    }

    public function testAddTodoRendersFormAndEscapesPostValues(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->page->addTodo(["_csrf_token" => $this->session['csrf_token'], "title" => '<b>', "text" => 'a"b', "due_date" => '2026-01-01'], []);

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

        $output = $this->page->addTodo(["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "", "text" => "", "due_date" => ""], []);

        $this->assertStringContainsString('Titel erforderlich', (string) $output);
        $this->assertStringContainsString('<p>Titel erforderlich</p>', (string) $output);
    }

    public function testAddTodoSaveSuccessRedirectsToIndex(): void
    {
        $output = RunCliHelper::run(
            '$page->addTodo($_POST, []);',
            ["user_id" => 1, "username" => "alice", "role" => "admin", "csrf_token" => $this->session['csrf_token']],
            [],
            ["_csrf_token" => $this->session['csrf_token'], "save" => "Speichern", "title" => "Neu", "text" => "Text", "priority" => "1", "due_date" => "2026-02-01"],
            '$pdo->exec("INSERT INTO categories (name,user_id) VALUES (\'Allgemein\',1)");',
        );

        $this->assertSame("Location: index.php", $output);
    }

    public function testAddTodoWithUploadStoresViaUploadsModuleAndRedirects(): void
    {
        $uploadsDir = sys_get_temp_dir() . '/legacy-todo-page-uploads-' . bin2hex(random_bytes(4));
        mkdir($uploadsDir, 0777, true);
        $tmp = tempnam(sys_get_temp_dir(), 'legacy-todo-page-up-');
        file_put_contents($tmp, 'file-body');

        try {
            $output = RunCliHelper::run(
                '$page->addTodo($_POST, $_FILES);',
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

    public function testAdminRendersUsersCategoriesAndTagsEscaped(): void
    {
        $this->pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (1,'<b>alice</b>','','admin','a@x.com','2026-01-01')");
        $this->pdo->exec("INSERT INTO categories (id,name,user_id) VALUES (1,'<i>Allgemein</i>',1)");
        $this->pdo->exec("INSERT INTO tags (id,name) VALUES (1,'<a>tag</a>')");
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->page->admin([]);

        $this->assertStringContainsString('<h1>Admin</h1>', $output);
        $this->assertStringContainsString('<li>&lt;b&gt;alice&lt;/b&gt; - admin - a@x.com</li>', $output);
        $this->assertStringContainsString('<li>&lt;i&gt;Allgemein&lt;/i&gt;</li>', $output);
        $this->assertStringContainsString('<li>&lt;a&gt;tag&lt;/a&gt;</li>', $output);
    }

    public function testAdminAddCategoryWithEmptyNameRendersNameFehlt(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->page->admin(["_csrf_token" => $this->session['csrf_token'], "add_cat" => "Kategorie", "kategorie" => "", "cat" => "", "category" => ""]);

        $this->assertStringContainsString('Name fehlt', $output);
    }

    public function testAdminAddTagCreatesTagAppearingInList(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->page->admin(["_csrf_token" => $this->session['csrf_token'], "add_tag" => "Tag", "tag" => "neu"]);

        $this->assertStringContainsString('<li>neu</li>', $output);
    }
}
