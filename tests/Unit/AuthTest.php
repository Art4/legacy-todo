<?php

declare(strict_types=1);

use Art4\LegacyTodo\Auth;

final class AuthTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var Auth */
    private $auth;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->session = [];
        $this->auth = new Auth($this->pdo, $this->session);
    }

    public function testCurrentUserIsNullWithoutLogin(): void
    {
        $this->assertNull($this->auth->currentUser());
    }

    public function testCurrentUserIsNullForEmptyUserId(): void
    {
        $this->session['user_id'] = '';

        $this->assertNull($this->auth->currentUser());
    }

    public function testCurrentUserReturnsIdentityFromSession(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'user';

        $user = $this->auth->currentUser();

        $this->assertSame(1, $user['user_id']);
        $this->assertSame('alice', $user['username']);
        $this->assertSame('user', $user['role']);
    }

    public function testLoggedInIsFalseWithoutUserId(): void
    {
        $this->assertFalse($this->auth->loggedIn());
    }

    public function testLoggedInIsFalseForEmptyAndNullUserId(): void
    {
        $this->session['user_id'] = '';
        $this->assertFalse($this->auth->loggedIn());

        $this->session['user_id'] = null;
        $this->assertFalse($this->auth->loggedIn());
    }

    public function testLoggedInIsTrueForSetUserId(): void
    {
        $this->session['user_id'] = 1;

        $this->assertTrue($this->auth->loggedIn());
    }

    private function seedUser(array $row): int
    {
        $this->pdo->exec(
            "INSERT INTO users (username,password,role,email,created_at) VALUES ("
            . "'" . $row["username"] . "','" . md5($row["password"]) . "','" . $row["role"] . "','" . $row["email"] . "','2026-01-01')",
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function testLoginWritesSessionOnSuccess(): void
    {
        $id = $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);

        $loggedIn = $this->auth->login("alice", "secret");

        $this->assertTrue($loggedIn);
        $this->assertSame($id, $this->session["user_id"]);
        $this->assertSame("alice", $this->session["username"]);
        $this->assertSame("user", $this->session["role"]);
    }

    public function testLoginReturnsFalseAndLeavesSessionUntouchedOnFailure(): void
    {
        $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);
        $this->session["keep"] = "value";

        $this->assertFalse($this->auth->login("alice", "wrong"));
        $this->assertFalse($this->auth->login("ghost", "secret"));

        $this->assertSame(["keep" => "value"], $this->session);
    }

    public function testLogoutClearsSession(): void
    {
        $this->session["user_id"] = 1;
        $this->session["username"] = "alice";
        $this->session["role"] = "user";

        $this->auth->logout();

        $this->assertFalse($this->auth->loggedIn());
        $this->assertSame([], $this->session);
    }

    public function testRequireLoginDoesNothingWhenLoggedIn(): void
    {
        $this->session["user_id"] = 1;

        $this->auth->requireLogin();

        $this->assertTrue($this->auth->loggedIn());
    }

    public function testRequireLoginRedirectsToLoginPhp(): void
    {
        $output = $this->runCli('$auth->requireLogin();', [], []);

        $this->assertSame("Location: login.php", $output);
    }

    public function testRequireLoginRedirectsToLoginPhpWithNext(): void
    {
        $output = $this->runCli('$auth->requireLogin("addtodo.php");', [], []);

        $this->assertSame("Location: login.php?next=addtodo.php", $output);
    }

    public function testRequireRoleAllowsAdmin(): void
    {
        $this->session["role"] = "admin";

        $this->auth->requireRole("admin");

        $this->assertTrue(true);
    }

    public function testRequireRoleStopsNonAdminPage(): void
    {
        $this->session["user_id"] = 1;
        $this->session["role"] = "user";

        $output = $this->runCli('$auth->requireRole("admin");', $this->session, []);

        $this->assertSame("Keine Rechte", $output);
    }

    private function runCli(string $body, array $session, array $get): string
    {
        $script = 'namespace Art4\\LegacyTodo { function header($line) { echo $line; } }'
            . 'namespace {'
            . 'require ' . var_export(__DIR__ . '/../../vendor/autoload.php', true) . ';'
            . '$_SESSION = ' . var_export($session, true) . ';'
            . '$_GET = ' . var_export($get, true) . ';'
            . '$auth = new Art4\\LegacyTodo\\Auth(new PDO("sqlite::memory:"), $_SESSION);'
            . $body
            . '}';

        exec(PHP_BINARY . ' -r ' . escapeshellarg($script), $lines, $code);

        $this->assertSame(0, $code, implode("\n", $lines));

        return trim(implode("", $lines));
    }
}