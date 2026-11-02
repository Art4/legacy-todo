<?php

declare(strict_types=1);

use Art4\LegacyTodo\Installer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Installer::class)]
final class InstallerTest extends PHPUnit\Framework\TestCase
{
    public function testInstallCreatesTheSchemaAndSeedsOnceOnly(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $installer = new Installer($pdo);

        $installer->install();
        $installer->install();

        $this->assertSame(
            ['2', '3'],
            [
                (string) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                (string) $pdo->query('SELECT COUNT(*) FROM todos')->fetchColumn(),
            ],
        );
    }
}
