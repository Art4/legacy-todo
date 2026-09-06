<?php

declare(strict_types=1);

use Art4\LegacyTodo\Users;

final class UsersTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var Users */
    private $users;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->users = new Users($this->pdo);
    }

    private function seedUser(array $row): int
    {
        $this->pdo->exec(
            "INSERT INTO users (username,password,role,email,created_at) VALUES ("
            . "'" . $row["username"] . "','" . md5($row["password"]) . "','" . $row["role"] . "','" . $row["email"] . "','2026-01-01')",
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function testFindByIdReturnsRowOrNull(): void
    {
        $id = $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);

        $row = $this->users->findById($id);

        $this->assertSame("alice", $row["username"]);
        $this->assertSame($id, $row["id"]);
        $this->assertNull($this->users->findById($id + 99));
    }

    public function testFindByUsernameReturnsRowOrNull(): void
    {
        $this->seedUser(["username" => "bob", "password" => "pw", "role" => "admin", "email" => "bob@example.com"]);

        $row = $this->users->findByUsername("bob");

        $this->assertSame("bob", $row["username"]);
        $this->assertSame("admin", $row["role"]);
        $this->assertNull($this->users->findByUsername("nobody"));
    }

    public function testAuthenticateReturnsRowForCorrectCredentials(): void
    {
        $id = $this->seedUser(["username" => "carol", "password" => "hunter2", "role" => "user", "email" => "carol@example.com"]);

        $row = $this->users->authenticate("carol", "hunter2");

        $this->assertSame($id, $row["id"]);
        $this->assertSame("carol", $row["username"]);
        $this->assertSame("user", $row["role"]);
    }

    public function testAuthenticateReturnsNullForWrongPassword(): void
    {
        $this->seedUser(["username" => "carol", "password" => "hunter2", "role" => "user", "email" => "carol@example.com"]);

        $this->assertNull($this->users->authenticate("carol", "wrong"));
    }

    public function testAuthenticateReturnsNullForUnknownUser(): void
    {
        $this->assertNull($this->users->authenticate("ghost", "pw"));
    }
}