<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\Fakes\RunCliHelper;
use Art4\LegacyTodo\Installer;

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
        (new Installer($pdo))->createSchema();
        $this->session = [];
        $app = new Bootstrap($pdo, $this->session, 'Legacy Todo');
        $this->session['csrf_token'] = $app->auth()->csrfToken();
        $this->csrf = new Csrf($app->auth(), $app->layout());
    }

    public function testFieldRendersCsrfHiddenInputWithEscapedToken(): void
    {
        $expected = '<input type="hidden" name="_csrf_token" value="' . $this->session['csrf_token'] . '">';

        $this->assertSame($expected, $this->csrf->field());
    }

    public function testEmptyPostPassesGuard(): void
    {
        $output = RunCliHelper::run(
            '$csrf = $app->csrf(); $csrf->guard($_POST); echo "ok";',
            [],
            [],
            [],
        );

        $this->assertSame('ok', $output);
    }

    public function testPostWithoutCsrfTokenRendersComposed403(): void
    {
        $output = RunCliHelper::run(
            '$csrf = $app->csrf(); $csrf->guard($_POST); echo "unreachable";',
            [],
            [],
            ["login" => "Login", "username" => "alice", "password" => "secret"],
        );

        $this->assertStringContainsString('CSRF token invalid', $output);
        $this->assertStringContainsString('<title>CSRF token invalid</title>', $output);
        $this->assertStringContainsString('</body></html>', $output);
    }

    public function testPostWithInvalidCsrfTokenRendersComposed403(): void
    {
        $output = RunCliHelper::run(
            '$csrf = $app->csrf(); $csrf->guard($_POST); echo "unreachable";',
            ["csrf_token" => $this->session['csrf_token']],
            [],
            ["_csrf_token" => "wrong-token", "login" => "Login", "username" => "alice", "password" => "secret"],
        );

        $this->assertStringContainsString('CSRF token invalid', $output);
        $this->assertStringContainsString('<title>CSRF token invalid</title>', $output);
        $this->assertStringContainsString('</body></html>', $output);
    }

    public function testPostWithValidCsrfTokenPassesGuard(): void
    {
        $output = RunCliHelper::run(
            '$csrf = $app->csrf(); $csrf->guard($_POST); echo "ok";',
            ["csrf_token" => $this->session['csrf_token']],
            [],
            ["_csrf_token" => $this->session['csrf_token'], "login" => "Login"],
        );

        $this->assertSame('ok', $output);
    }
}
