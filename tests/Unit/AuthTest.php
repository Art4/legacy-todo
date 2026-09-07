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
}