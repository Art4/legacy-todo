<?php

declare(strict_types=1);

namespace Art4\LegacyTodo;

/**
 * Boots a fresh isolated app instance per test method: a PHP built-in server
 * against the repo's real docroot (public/), a fresh temp SQLite file wired
 * in through the LEGACY_TODO_DB_FILE env seam, and one cookie jar per test.
 */
abstract class E2eTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $dbFile;

    /** @var string */
    private $cookieJar;

    /** @var string */
    private $serverStderr;

    /** @var string */
    private $serverStdout;

    /** @var resource|null */
    private $serverProcess;

    /** @var list<string> */
    private $uploadedFiles = [];

    /** @var HttpClient */
    protected $http;

    protected function setUp(): void
    {
        $dbPath = tempnam(sys_get_temp_dir(), 'e2e-db-');
        if ($dbPath !== false) {
            @unlink($dbPath);
        }
        $this->dbFile = $dbPath . '.sqlite';
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'e2e-cookies-');
        $this->serverStderr = (string) tempnam(sys_get_temp_dir(), 'e2e-server-err-');
        $this->serverStdout = (string) tempnam(sys_get_temp_dir(), 'e2e-server-out-');

        Bootstrap::install(['db_file' => $this->dbFile]);

        $docroot = dirname(__DIR__) . '/../public';

        $port = $this->freePort();
        $env = getenv();
        $this->serverProcess = proc_open(
            sprintf('%s -S 127.0.0.1:%d -t %s', escapeshellarg(PHP_BINARY), $port, escapeshellarg($docroot)),
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', $this->serverStdout, 'a'],
                2 => ['file', $this->serverStderr, 'a'],
            ],
            $pipes,
            $docroot,
            array_merge($env === false ? [] : $env, ['LEGACY_TODO_DB_FILE' => $this->dbFile]),
        );
        if ($this->serverProcess === false) {
            $this->throwSetupFailure('could not start the PHP built-in server process');
        }

        $this->waitUntilReady($port);
        $this->http = new HttpClient(sprintf('http://127.0.0.1:%d', $port), $this->cookieJar);
    }

    protected function tearDown(): void
    {
        $hasFailed = method_exists($this, 'status')
            ? ($this->status()->isError() || $this->status()->isFailure())
            : $this->hasFailed();
        if ($hasFailed) {
            $tail = $this->tailFile($this->serverStderr);
            if ($tail !== '') {
                fwrite(STDERR, "\n[E2E server stderr tail]\n" . $tail);
            }
        }
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
            $this->serverProcess = null;
        }
        foreach ($this->uploadedFiles as $file) {
            @unlink($file);
        }
        @unlink($this->dbFile);
        @unlink($this->cookieJar);
        @unlink($this->serverStderr);
        @unlink($this->serverStdout);
    }

    protected function login(string $username, string $password): E2eResponse
    {
        $token = $this->csrfTokenFromLoginForm();
        $response = $this->http->request('POST', '/login.php', [
            '_csrf_token' => $token,
            'login' => '1',
            'username' => $username,
            'password' => $password,
        ]);
        $this->assertRedirect($response, 'index.php');

        return $response;
    }

    protected function csrfTokenFromLoginForm(): string
    {
        $form = $this->http->request('GET', '/login.php');

        return $this->csrfTokenFrom($form);
    }

    protected function csrfTokenFrom(E2eResponse $response): string
    {
        if (preg_match('/name="_csrf_token" value="([^"]+)"/', $response->body(), $match) !== 1) {
            $this->fail('no _csrf_token field found in response body');
        }

        return $match[1];
    }

    protected function assertRedirect(E2eResponse $response, string $target): void
    {
        $this->assertGreaterThanOrEqual(300, $response->status());
        $this->assertLessThan(400, $response->status());
        $this->assertSame($target, $response->location());
    }

    protected function dbPdo(): \PDO
    {
        $pdo = new \PDO('sqlite:' . $this->dbFile);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    protected function trackUploadedFile(string $path): void
    {
        $this->uploadedFiles[] = $path;
    }

    private function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            $this->throwSetupFailure('could not allocate a free port');
        }
        $address = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($address, strrpos($address, ':') + 1);
    }

    private function waitUntilReady(int $port): void
    {
        $url = sprintf('http://127.0.0.1:%d/login.php', $port);
        $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 1]]);
        $deadline = microtime(true) + 8.0;
        while (microtime(true) < $deadline) {
            $status = is_resource($this->serverProcess) ? proc_get_status($this->serverProcess) : false;
            if ($status === false || !$status['running']) {
                $this->throwSetupFailure('the PHP built-in server exited before becoming ready');
            }
            $body = @file_get_contents($url, false, $context);
            if ($body !== false) {
                return;
            }
            usleep(200000);
        }
        $this->throwSetupFailure('the PHP built-in server did not become ready within 8s');
    }

    private function throwSetupFailure(string $message): void
    {
        $tail = $this->tailFile($this->serverStderr);
        throw new \RuntimeException($message . ($tail !== '' ? ":\n" . $tail : ''));
    }

    /** @return string the last 30 lines of the file, or '' when unreadable */
    private function tailFile(string $file): string
    {
        $lines = @file($file);
        if ($lines === false) {
            return '';
        }
        $tail = array_slice($lines, -30);

        return implode('', $tail);
    }
}
