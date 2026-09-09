<?php

declare(strict_types=1);

final class PublicWebrootTest extends PHPUnit\Framework\TestCase
{
    private const ENTRY_POINTS = [
        'addtodo.php',
        'admin.php',
        'deletetodo.php',
        'edittodo.php',
        'index.php',
        'login.php',
        'logout.php',
        'todo.php',
    ];

    private const INTERNAL_FILES = [
        'composer.json',
        'composer.lock',
        'database.sqlite',
        'phpstan-baseline.neon',
        'phpstan.neon',
        'psalm-baseline.xml',
        'psalm.xml',
        'rector.php',
    ];

    private function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public function testEveryEntryPointLivesInThePublicWebroot(): void
    {
        $webroot = $this->repoRoot() . '/public';

        foreach (self::ENTRY_POINTS as $entryPoint) {
            $this->assertFileExists(
                $webroot . '/' . $entryPoint,
                sprintf('%s must be served from the public/ webroot', $entryPoint),
            );
        }
    }

    public function testEntryPointStartsViaTheBootstrapModule(): void
    {
        $webroot = $this->repoRoot() . '/public';

        foreach (self::ENTRY_POINTS as $entryPoint) {
            $path = $webroot . '/' . $entryPoint;
            $this->assertFileExists($path);
            $source = (string) file_get_contents($path);
            $this->assertStringContainsString(
                '__DIR__ . "/../src/Bootstrap.php"',
                $source,
                sprintf('%s must bootstrap through ../src/Bootstrap.php', $entryPoint),
            );
        }
    }

    public function testInternalFilesAreOutsideTheWebroot(): void
    {
        $webroot = $this->repoRoot() . '/public';

        foreach (self::INTERNAL_FILES as $internalFile) {
            $this->assertFileDoesNotExist(
                $webroot . '/' . $internalFile,
                sprintf('%s must not be servable from the public/ webroot', $internalFile),
            );
        }
    }
}
