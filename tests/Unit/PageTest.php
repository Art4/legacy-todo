<?php

declare(strict_types=1);

use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Page;

final class PageTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var Bootstrap */
    private $app;

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
        $this->app = new Bootstrap($this->pdo, $this->session, 'Legacy Todo');
        $this->page = new Page($this->app);
    }

    public function testTextEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $this->page->text('<script>alert("x")</script>'));
    }

    public function testAttrEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;i&gt;&amp;&quot;q&quot;&lt;/i&gt;', $this->page->attr('<i>&"q"</i>'));
    }

    public function testHeaderRendersSiteNameFromBootstrap(): void
    {
        $output = $this->page->header();

        $this->assertStringContainsString('<title>Legacy Todo - Legacy Todo</title>', $output);
        $this->assertStringContainsString('<h2>Legacy Todo</h2>', $output);
    }

    public function testHeaderRendersLoggedInUserFromAuthWhenLoggedIn(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'user';

        $output = $this->page->header();

        $this->assertStringContainsString('<p>Eingeloggt als alice</p>', $output);
    }

    public function testHeaderOmitsLoggedInUserWhenAnonymous(): void
    {
        $output = $this->page->header();

        $this->assertStringNotContainsString('Eingeloggt als', $output);
    }

    public function testFooterRendersVersion(): void
    {
        $output = $this->page->footer();

        $this->assertStringContainsString('<p>&copy; 2026 LegacyTodo - Version 0.1</p>', $output);
    }

    private function seedUser(array $row): int
    {
        $this->pdo->exec(
            "INSERT INTO users (username,password,role,email,created_at) VALUES ("
            . "'" . $row["username"] . "','" . md5($row["password"]) . "','" . $row["role"] . "','" . $row["email"] . "','2026-01-01')",
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function testLoginShowsLoginFormOnGetRequest(): void
    {
        $output = $this->page->login([]);

        $this->assertStringContainsString('<h1>Login</h1>', $output);
        $this->assertStringContainsString("name='username'", $output);
        $this->assertStringContainsString("name='password'", $output);
        $this->assertStringContainsString('name="register"', $output);
    }

    public function testLoginEscapesUsernameAndEmailInFormFields(): void
    {
        $output = $this->page->login(["username" => '<a href="#">', "email" => 'a"&b']);

        $this->assertStringContainsString("value='&lt;a href=&quot;#&quot;&gt;'", $output);
        $this->assertStringContainsString('value="a&quot;&amp;b"', $output);
    }

    public function testLoginFailureRendersLoginFailedMessageAndEscapesIt(): void
    {
        $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);

        $output = $this->page->login(["login" => "Login", "username" => "alice", "password" => "wrong"]);

        $this->assertStringContainsString('Login failed', $output);
        $this->assertStringContainsString('<p>Login failed</p>', $output);
    }

    public function testRegisterSuccessRendersRegistriertMessage(): void
    {
        $output = $this->page->login(["register" => "Registrieren", "username" => "bob", "password" => "pw", "email" => "bob@example.com"]);

        $this->assertStringContainsString('<p>Registriert</p>', $output);
    }

    public function testRegisterWithEmptyFieldsReportsRegistriertDueToLooseComparison(): void
    {
        $output = $this->page->login(["register" => "Registrieren", "username" => "", "password" => "", "email" => "x@example.com"]);

        $this->assertStringContainsString('<p>Registriert</p>', $output);
        $this->assertStringNotContainsString('Fehler:', $output);
    }

    public function testLoginSuccessRedirectsToIndex(): void
    {
        $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);

        $output = $this->runCli(
            'echo $page->login($_POST);',
            [],
            [],
            ["login" => "Login", "username" => "alice", "password" => "secret"],
        );

        $this->assertSame("Location: index.php", $output);
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

    public function testDeleteTodoRendersConfirmPageAndEscapesTitleAndId(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => '<b>Wichtig</b>', "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01"]);
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->page->deleteTodo($id, []);

        $this->assertStringContainsString('<h1>Löschen?</h1>', $output);
        $this->assertStringContainsString('<p>&lt;b&gt;Wichtig&lt;/b&gt; wirklich archivieren?</p>', $output);
        $this->assertStringContainsString('href="deletetodo.php?id=' . $id . '&confirm=1"', $output);
    }

    public function testDeleteTodoConfirmArchivesAndRedirectsToIndex(): void
    {
        $output = $this->runCli(
            '$page->deleteTodo((int) $get["id"], $get);',
            ["user_id" => 1, "username" => "alice", "role" => "admin"],
            ["id" => 1, "confirm" => "1"],
            [],
            '$pdo->exec("INSERT INTO todos (user_id,title,text,status,archived,created_at) VALUES (1,\'Wichtig\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Location: index.php", $output);
    }

    public function testDeleteTodoDeniesWhenCannotManage(): void
    {
        $output = $this->runCli(
            'echo $page->deleteTodo((int) $get["id"], $get);',
            ["user_id" => 9, "username" => "eve", "role" => "user"],
            ["id" => 1],
            [],
            '$pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (9,\'eve\',\'\',\'user\',\'e@x.com\',\'2026-01-01\')");'
                . '$pdo->exec("INSERT INTO todos (id,user_id,title,text,status,archived,created_at) VALUES (1,5,\'Fremdes\',\'\',\'open\',0,\'2026-01-10\')");',
        );

        $this->assertSame("Keine Berechtigung", $output);
    }

    private function runCli(string $body, array $session, array $get, array $post, string $setup = ''): string
    {
        $hash = md5('secret');
        $script = 'namespace Art4\\LegacyTodo { function header($line) { echo $line; } }'
            . 'namespace {'
            . 'require ' . var_export(__DIR__ . '/../../vendor/autoload.php', true) . ';'
            . '$pdo = new PDO("sqlite::memory:");'
            . '$pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)");'
            . '$pdo->exec("CREATE TABLE todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)");'
            . '$pdo->exec("CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)");'
            . '$pdo->exec("CREATE TABLE tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");'
            . '$pdo->exec("CREATE TABLE todo_tags (todo_id INTEGER, tag_id INTEGER)");'
            . '$pdo->exec("CREATE TABLE comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)");'
            . '$pdo->exec("CREATE TABLE assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)");'
            . '$pdo->exec("INSERT INTO users (username,password,role,email,created_at) VALUES (\'alice\',\'' . $hash . '\',\'user\',\'alice@example.com\',\'2026-01-01\')");'
            . $setup
            . '$_SESSION = ' . var_export($session, true) . ';'
            . '$_GET = ' . var_export($get, true) . ';'
            . '$_POST = ' . var_export($post, true) . ';'
            . '$app = new Art4\\LegacyTodo\\Bootstrap($pdo, $_SESSION, "Legacy Todo");'
            . '$page = new Art4\\LegacyTodo\\Page($app);'
            . '$get = ' . var_export($get, true) . ';'
            . '$session = ' . var_export($session, true) . ';'
            . '$post = ' . var_export($post, true) . ';'
            . $body
            . '}';

        exec(PHP_BINARY . ' -r ' . escapeshellarg($script), $lines, $code);

        $this->assertSame(0, $code, implode("\n", $lines));

        return trim(implode("", $lines));
    }
}
