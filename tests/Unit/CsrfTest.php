<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Layout;

final class CsrfTest extends PHPUnit\Framework\TestCase
{
    /** @var array<string, mixed> */
    private $session;

    /** @var Csrf */
    private $csrf;

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
        $this->csrf = new Csrf($app->auth(), new Layout($app));
    }

    public function testFieldRendersCsrfHiddenInputWithEscapedToken(): void
    {
        $expected = '<input type="hidden" name="_csrf_token" value="' . $this->session['csrf_token'] . '">';

        $this->assertSame($expected, $this->csrf->field());
    }

    public function testEmptyPostPassesGuard(): void
    {
        $output = RunCliHelper::run(
            '$csrf = new \Art4\LegacyTodo\Csrf($app->auth(), new \Art4\LegacyTodo\Layout($app)); $csrf->guard($_POST); echo "ok";',
            [],
            [],
            [],
        );

        $this->assertSame('ok', $output);
    }

    public function testPostWithoutCsrfTokenReturns403(): void
    {
        $output = RunCliHelper::run(
            '$csrf = new \Art4\LegacyTodo\Csrf($app->auth(), new \Art4\LegacyTodo\Layout($app)); $csrf->guard($_POST); echo "unreachable";',
            [],
            [],
            ["login" => "Login", "username" => "alice", "password" => "secret"],
        );

        $this->assertSame('CSRF token invalid', $output);
    }

    public function testPostWithInvalidCsrfTokenReturns403(): void
    {
        $output = RunCliHelper::run(
            '$csrf = new \Art4\LegacyTodo\Csrf($app->auth(), new \Art4\LegacyTodo\Layout($app)); $csrf->guard($_POST); echo "unreachable";',
            ["csrf_token" => $this->session['csrf_token']],
            [],
            ["_csrf_token" => "wrong-token", "login" => "Login", "username" => "alice", "password" => "secret"],
        );

        $this->assertSame('CSRF token invalid', $output);
    }

    public function testPostWithValidCsrfTokenPassesGuard(): void
    {
        $output = RunCliHelper::run(
            '$csrf = new \Art4\LegacyTodo\Csrf($app->auth(), new \Art4\LegacyTodo\Layout($app)); $csrf->guard($_POST); echo "ok";',
            ["csrf_token" => $this->session['csrf_token']],
            [],
            ["_csrf_token" => $this->session['csrf_token'], "login" => "Login"],
        );

        $this->assertSame('ok', $output);
    }
}