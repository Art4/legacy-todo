<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Layout;
use Art4\LegacyTodo\LoginHandler;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Users;

final class LoginHandlerTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var LoginHandler */
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
        $todos = new Todos($this->pdo, new Taxonomy($this->pdo));
        $auth = new Auth($users, $todos, $this->session);
        $this->session['csrf_token'] = $auth->csrfToken();
        $layout = new Layout('Legacy Todo', $auth);
        $this->handler = new LoginHandler($auth, $users, new Csrf($auth, $layout), $layout, 'Legacy Todo');
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
        $output = $this->handler->handle([]);

        $this->assertStringContainsString('<h1>Login</h1>', $output);
        $this->assertStringContainsString("name='username'", $output);
        $this->assertStringContainsString("name='password'", $output);
        $this->assertStringContainsString('name="register"', $output);
        $this->assertStringContainsString('name="_csrf_token"', $output);
    }

    public function testLoginEscapesUsernameAndEmailInFormFields(): void
    {
        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "username" => '<a href="#">', "email" => 'a"&b']);

        $this->assertStringContainsString("value='&lt;a href=&quot;#&quot;&gt;'", $output);
        $this->assertStringContainsString('value="a&quot;&amp;b"', $output);
    }

    public function testLoginFailureRendersLoginFailedMessageAndEscapesIt(): void
    {
        $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "login" => "Login", "username" => "alice", "password" => "wrong"]);

        $this->assertStringContainsString('Login failed', $output);
        $this->assertStringContainsString('<p>Login failed</p>', $output);
    }

    public function testRegisterSuccessRendersRegistriertMessage(): void
    {
        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "register" => "Registrieren", "username" => "bob", "password" => "pw", "email" => "bob@example.com"]);

        $this->assertStringContainsString('<p>Registriert</p>', $output);
    }

    public function testRegisterWithEmptyFieldsReportsErrorAndWritesNoUser(): void
    {
        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "register" => "Registrieren", "username" => "", "password" => "", "email" => "x@example.com"]);

        $this->assertStringContainsString('Fehler: Benutzername und Passwort sind erforderlich.', $output);
        $this->assertStringNotContainsString('Registriert', $output);
        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn());
    }

    public function testRegisterWithTakenUsernameRendersError(): void
    {
        $this->seedUser(["username" => "alice", "password" => "pw", "role" => "user", "email" => "alice@example.com"]);

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "register" => "Registrieren", "username" => "alice", "password" => "pw2", "email" => "x@example.com"]);

        $this->assertStringContainsString('Fehler: Dieser Benutzername ist bereits vergeben.', $output);
        $this->assertStringNotContainsString('Registriert', $output);
    }

    public function testRegisterErrorMessageRenderedOnce(): void
    {
        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "register" => "Registrieren", "username" => "", "password" => "", "email" => "x@example.com"]);

        $this->assertSame(1, substr_count($output, 'Fehler: Benutzername und Passwort sind erforderlich.'));
    }

    public function testRegisterStorageFailureRendersErrorMessage(): void
    {
        $this->pdo->exec("CREATE TRIGGER fail_register BEFORE INSERT ON users BEGIN SELECT raise(ABORT, 'boom'); END;");

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "register" => "Registrieren", "username" => "jon", "password" => "pw", "email" => "jon@example.com"]);

        $this->assertStringContainsString('Fehler: Registrierung fehlgeschlagen.', $output);
    }

    public function testLoginSuccessRedirectsToIndex(): void
    {
        $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);

        $output = RunCliHelper::run(
            '$h = new \Art4\LegacyTodo\LoginHandler($app->auth(), $app->users(), $app->csrf(), $app->layout(), $app->siteName()); echo $h->handle($_POST);',
            $this->session,
            [],
            ["_csrf_token" => $this->session['csrf_token'], "login" => "Login", "username" => "alice", "password" => "secret"],
        );

        $this->assertSame("Location: index.php", $output);
    }
}
