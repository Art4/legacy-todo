<?php

declare(strict_types=1);

use Art4\LegacyTodo\RegistrationResult;
use Art4\LegacyTodo\User;
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
            . "'" . $row["username"] . "','" . password_hash($row["password"], PASSWORD_BCRYPT) . "','" . $row["role"] . "','" . $row["email"] . "','2026-01-01')",
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function testFindByIdReturnsUserOrNull(): void
    {
        $id = $this->seedUser(["username" => "alice", "password" => "secret", "role" => "user", "email" => "alice@example.com"]);

        $user = $this->users->findById($id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame("alice", $user->username());
        $this->assertSame($id, $user->id());
        $this->assertSame("user", $user->role());
        $this->assertSame("alice@example.com", $user->email());
        $this->assertNull($this->users->findById($id + 99));
    }

    public function testFindByUsernameReturnsUserOrNull(): void
    {
        $this->seedUser(["username" => "bob", "password" => "pw", "role" => "admin", "email" => "bob@example.com"]);

        $user = $this->users->findByUsername("bob");

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame("bob", $user->username());
        $this->assertSame("admin", $user->role());
        $this->assertSame("bob@example.com", $user->email());
        $this->assertNull($this->users->findByUsername("nobody"));
    }

    public function testAuthenticateReturnsUserForCorrectCredentials(): void
    {
        $id = $this->seedUser(["username" => "carol", "password" => "hunter2", "role" => "user", "email" => "carol@example.com"]);

        $user = $this->users->authenticate("carol", "hunter2");

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($id, $user->id());
        $this->assertSame("carol", $user->username());
        $this->assertSame("user", $user->role());
        $this->assertSame("carol@example.com", $user->email());
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

    public function testAuthenticateRejectsInjectedUsername(): void
    {
        $this->seedUser(["username" => "admin", "password" => "pw", "role" => "admin", "email" => "admin@example.com"]);

        $this->assertNull($this->users->authenticate("admin' OR '1'='1", "x"));
    }

    public function testAuthenticateUpgradesLegacyMd5HashToBcrypt(): void
    {
        $this->pdo->exec(
            "INSERT INTO users (username,password,role,email,created_at) VALUES ("
            . "'gina','" . md5("secret") . "','user','gina@example.com','2026-01-01')",
        );

        $user = $this->users->authenticate("gina", "secret");

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame("gina", $user->username());
        $this->assertSame("user", $user->role());
        $stored = $this->pdo->query("SELECT password FROM users WHERE username='gina'")->fetchColumn();
        $this->assertTrue(password_verify("secret", $stored));
        $this->assertNotSame(md5("secret"), $stored);
    }

    public function testAuthenticateFailsForWrongPasswordAgainstLegacyMd5Hash(): void
    {
        $this->pdo->exec(
            "INSERT INTO users (username,password,role,email,created_at) VALUES ("
            . "'hank','" . md5("secret") . "','user','hank@example.com','2026-01-01')",
        );

        $this->assertNull($this->users->authenticate("hank", "wrong"));
    }

    public function testAuthenticateUpgradesLowCostBcryptHashToCurrentDefault(): void
    {
        $legacy = password_hash("secret", PASSWORD_BCRYPT, ["cost" => 4]);
        $this->pdo->exec(
            "INSERT INTO users (username,password,role,email,created_at) VALUES ("
            . "'lena','" . $legacy . "','user','lena@example.com','2026-01-01')",
        );

        $user = $this->users->authenticate("lena", "secret");

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame("lena", $user->username());
        $this->assertSame("user", $user->role());
        $stored = $this->pdo->query("SELECT password FROM users WHERE username='lena'")->fetchColumn();
        $this->assertNotSame($legacy, $stored);
        $this->assertTrue(password_verify("secret", $stored));
        $this->assertFalse(password_needs_rehash((string) $stored, PASSWORD_BCRYPT));
    }

    public function testRegisterRejectsInjectedUsername(): void
    {
        $result = $this->users->register("a'--", "pw", "e@e.com");

        $this->assertInstanceOf(RegistrationResult::class, $result);
        $this->assertTrue($result->isRegistered());
        $row = $this->pdo->query("SELECT * FROM users")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("a'--", $row["username"]);
    }

    public function testRegisterInsertsUserWithUserRole(): void
    {
        $result = $this->users->register("dave", "pw123", "dave@example.com");

        $this->assertInstanceOf(RegistrationResult::class, $result);
        $this->assertTrue($result->isRegistered());
        $row = $this->pdo->query("SELECT * FROM users")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("dave", $row["username"]);
        $this->assertTrue(password_verify("pw123", $row["password"]));
        $this->assertSame("user", $row["role"]);
        $this->assertSame("dave@example.com", $row["email"]);
        $this->assertSame(date("Y-m-d"), $row["created_at"]);
    }

    public function testRegisterRejectsEmptyUsernameOrPassword(): void
    {
        $result1 = $this->users->register("", "pw", "e@example.com");
        $result2 = $this->users->register("dave", "", "e@example.com");

        $this->assertSame(RegistrationResult::STATUS_INVALID_INPUT, $result1->status());
        $this->assertSame(RegistrationResult::STATUS_INVALID_INPUT, $result2->status());
        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn());
    }

    public function testRegisterRejectsDuplicateUsername(): void
    {
        $this->seedUser(["username" => "erin", "password" => "pw", "role" => "user", "email" => "erin@example.com"]);

        $result = $this->users->register("erin", "other", "other@example.com");

        $this->assertSame(RegistrationResult::STATUS_USERNAME_TAKEN, $result->status());

        $rows = $this->pdo->query("SELECT * FROM users")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
        $this->assertTrue(password_verify("pw", $rows[0]["password"]));
    }

    public function testRegisterReturnsStorageFailureWhenInsertFails(): void
    {
        $this->pdo->exec("CREATE TRIGGER fail_register BEFORE INSERT ON users BEGIN SELECT raise(ABORT, 'boom'); END;");

        $result = $this->users->register("julia", "pw", "julia@example.com");

        $this->assertSame(RegistrationResult::STATUS_STORAGE_FAILURE, $result->status());
    }

    public function testCreateInsertsUserWithGivenRoleAndExampleComEmail(): void
    {
        $result = $this->users->create("frank", "pw456", "admin");

        $this->assertTrue($result);
        $row = $this->pdo->query("SELECT * FROM users")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("frank", $row["username"]);
        $this->assertTrue(password_verify("pw456", $row["password"]));
        $this->assertSame("admin", $row["role"]);
        $this->assertSame("frank@example.com", $row["email"]);
        $this->assertSame(date("Y-m-d"), $row["created_at"]);
    }

    public function testCreateReturnsFalseWhenInsertFails(): void
    {
        $this->pdo->exec("DROP TABLE users");

        $this->assertFalse($this->users->create("kevin", "pw789", "user"));
    }

    public function testListReturnsAllUsers(): void
    {
        $aliceId = $this->seedUser(["username" => "alice", "password" => "a", "role" => "user", "email" => "a@example.com"]);
        $bobId = $this->seedUser(["username" => "bob", "password" => "b", "role" => "admin", "email" => "b@example.com"]);

        $users = $this->users->listAll();

        $this->assertCount(2, $users);
        $this->assertSame([$aliceId, $bobId], array_map(fn(User $user) => $user->id(), $users));
        $this->assertSame(["alice", "bob"], array_map(fn(User $user) => $user->username(), $users));
    }
}
