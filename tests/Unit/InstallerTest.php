<?php

declare(strict_types=1);

use Art4\LegacyTodo\Installer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Installer::class)]
final class InstallerTest extends PHPUnit\Framework\TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
    }

    private function rows(string $table): string
    {
        return (string) $this->pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
    }

    public function testInstallCreatesEveryTable(): void
    {
        (new Installer($this->pdo))->install();

        $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertSame(['assignments', 'categories', 'comments', 'tags', 'todos', 'users'], $tables);
    }

    public function testInstallSeedsAnEmptyDatabase(): void
    {
        (new Installer($this->pdo))->install();

        $this->assertSame(['2', '3'], [$this->rows('users'), $this->rows('todos')]);
    }

    public function testInstallTwiceSeedsOnlyOnce(): void
    {
        $installer = new Installer($this->pdo);

        $installer->install();
        $installer->install();

        $this->assertSame(['2', '3'], [$this->rows('users'), $this->rows('todos')]);
    }

    public function testInstallLeavesADatabaseWithUsersUntouched(): void
    {
        $installer = new Installer($this->pdo);
        $installer->createSchema();
        $this->pdo->exec("INSERT INTO users (username, password, role, email, created_at) VALUES ('own', 'x', 'user', 'own@example.com', '2026-01-01')");

        $installer->install();

        $this->assertSame(['1', '0'], [$this->rows('users'), $this->rows('todos')]);
    }
}
