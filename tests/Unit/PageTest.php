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
            ["login" => "Login", "username" => "alice", "password" => "secret"],
        );

        $this->assertSame("Location: index.php", $output);
    }

    private function runCli(string $body, array $post): string
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
            . '$_SESSION = [];'
            . '$_POST = ' . var_export($post, true) . ';'
            . '$app = new Art4\\LegacyTodo\\Bootstrap($pdo, $_SESSION, "Legacy Todo");'
            . '$page = new Art4\\LegacyTodo\\Page($app);'
            . $body
            . '}';

        exec(PHP_BINARY . ' -r ' . escapeshellarg($script), $lines, $code);

        $this->assertSame(0, $code, implode("\n", $lines));

        return trim(implode("", $lines));
    }
}
