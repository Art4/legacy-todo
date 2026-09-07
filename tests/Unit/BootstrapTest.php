<?php

declare(strict_types=1);

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\TodoActivity;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Users;

final class BootstrapTest extends PHPUnit\Framework\TestCase
{
    /** @var array<string, mixed> */
    private $session;

    /** @var Bootstrap */
    private $bootstrap;

    protected function setUp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->session = [];
        $this->bootstrap = new Bootstrap($pdo, $this->session);
    }

    public function testSiteNameReturnsConfiguredSiteName(): void
    {
        $this->assertSame('Legacy Todo', $this->bootstrap->siteName());
    }

    public function testAuthReturnsAuthModule(): void
    {
        $this->assertInstanceOf(Auth::class, $this->bootstrap->auth());
    }

    public function testTodosReturnsTodosModule(): void
    {
        $this->assertInstanceOf(Todos::class, $this->bootstrap->todos());
    }

    public function testUsersReturnsUsersModule(): void
    {
        $this->assertInstanceOf(Users::class, $this->bootstrap->users());
    }

    public function testTodoActivityReturnsTodoActivityModule(): void
    {
        $this->assertInstanceOf(TodoActivity::class, $this->bootstrap->todoActivity());
    }

    public function testTaxonomyReturnsTaxonomyModule(): void
    {
        $this->assertInstanceOf(Taxonomy::class, $this->bootstrap->taxonomy());
    }

    public function testAuthIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->auth(), $this->bootstrap->auth());
    }

    public function testTodosIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->todos(), $this->bootstrap->todos());
    }

    public function testUsersIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->users(), $this->bootstrap->users());
    }

    public function testTodoActivityIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->todoActivity(), $this->bootstrap->todoActivity());
    }

    public function testTaxonomyIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->taxonomy(), $this->bootstrap->taxonomy());
    }

    public function testCurrentUserDelegatesToAuthThroughInjectedSession(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'user';

        $user = $this->bootstrap->currentUser();

        $this->assertSame(1, $user['user_id']);
        $this->assertSame('alice', $user['username']);
        $this->assertSame('user', $user['role']);
    }

    public function testCurrentUserIsNullWithoutLogin(): void
    {
        $this->assertNull($this->bootstrap->currentUser());
    }

    public function testStartBootsRealRequestContext(): void
    {
        $app = Bootstrap::start();

        $this->assertInstanceOf(Bootstrap::class, $app);
        $this->assertInstanceOf(Auth::class, $app->auth());

        session_destroy();
        session_write_close();
    }
}