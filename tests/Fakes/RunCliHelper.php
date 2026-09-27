<?php

declare(strict_types=1);

namespace Art4\LegacyTodo\Fakes;

/**
 * Shared subprocess harness for testing classes that call header()/exit().
 * Spawns a PHP CLI process, sets up a fresh in-memory database + session,
 * and executes an arbitrary expression, returning its stdout (trimmed).
 */
final class RunCliHelper
{
    /** @param array<string, mixed> $session */
    public static function run(
        string $body,
        array $session,
        array $get,
        array $post,
        string $setup = '',
        string $uploadsDir = '',
        array $files = []
    ): string {
        $hash = md5('secret');
        $moveOverride = '';
        if ($uploadsDir !== '') {
            $moveOverride = ' function move_uploaded_file($source, $dest) { return copy($source, $dest); } ';
        }
        $bootstrapArgs = 'new \Art4\LegacyTodo\Bootstrap($pdo, $_SESSION, "Legacy Todo"';
        if ($uploadsDir !== '') {
            $bootstrapArgs .= ', ' . var_export($uploadsDir, true);
        }
        $bootstrapArgs .= ')';
        $script = 'namespace Art4\\LegacyTodo { function header($line) { echo $line; }'
            . $moveOverride
            . '}'
            . 'namespace {'
            . 'require ' . var_export(__DIR__ . '/../../vendor/autoload.php', true) . ';'
            . '$pdo = new PDO("sqlite::memory:");'
            . '(new \Art4\LegacyTodo\Installer($pdo))->createSchema();'
            . '$pdo->exec("INSERT INTO users (username,password,role,email,created_at) VALUES (\'alice\',\'' . $hash . '\',\'user\',\'alice@example.com\',\'2026-01-01\')");'
            . $setup
            . '$_SESSION = ' . var_export($session, true) . ';'
            . '$_GET = ' . var_export($get, true) . ';'
            . '$_POST = ' . var_export($post, true) . ';'
            . '$_FILES = ' . var_export($files, true) . ';'
            . '$app = ' . $bootstrapArgs . ';'
            . '$get = ' . var_export($get, true) . ';'
            . '$session = ' . var_export($session, true) . ';'
            . '$post = ' . var_export($post, true) . ';'
            . $body
            . '}';

        exec(PHP_BINARY . ' -r ' . escapeshellarg($script), $lines, $code);

        if ($code !== 0) {
            throw new \RuntimeException("runCli failed (exit code $code):\n" . implode("\n", $lines));
        }

        return trim(implode('', $lines));
    }
}
