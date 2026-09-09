<?php

declare(strict_types=1);

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Dashboard;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\TodoActivity;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Uploads;
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

    public function testUploadsReturnsUploadsModule(): void
    {
        $this->assertInstanceOf(Uploads::class, $this->bootstrap->uploads());
    }

    public function testTodoActivityReturnsTodoActivityModule(): void
    {
        $this->assertInstanceOf(TodoActivity::class, $this->bootstrap->todoActivity());
    }

    public function testTaxonomyReturnsTaxonomyModule(): void
    {
        $this->assertInstanceOf(Taxonomy::class, $this->bootstrap->taxonomy());
    }

    public function testDashboardReturnsDashboardModule(): void
    {
        $this->assertInstanceOf(Dashboard::class, $this->bootstrap->dashboard());
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

    public function testUploadsIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->uploads(), $this->bootstrap->uploads());
    }

    public function testTodoActivityIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->todoActivity(), $this->bootstrap->todoActivity());
    }

    public function testTaxonomyIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->taxonomy(), $this->bootstrap->taxonomy());
    }

    public function testDashboardIsSharedSingleton(): void
    {
        $this->assertSame($this->bootstrap->dashboard(), $this->bootstrap->dashboard());
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

    public function testStartWithTempDbCreatesSchema(): void
    {
        $dbFile = $this->tempDb();

        Bootstrap::start(['db_file' => $dbFile]);

        $pdo = new \PDO('sqlite:' . $dbFile);
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
        @unlink($dbFile);

        $this->assertSame(
            ['assignments', 'categories', 'comments', 'tags', 'todo_tags', 'todos', 'users'],
            $tables,
        );
    }

    public function testStartWithTempDbSeedsWhenEmpty(): void
    {
        $dbFile = $this->tempDb();

        Bootstrap::start(['db_file' => $dbFile]);

        $pdo = new \PDO('sqlite:' . $dbFile);
        $users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $todos = (int) $pdo->query('SELECT COUNT(*) FROM todos')->fetchColumn();
        $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        $tags = (int) $pdo->query('SELECT COUNT(*) FROM tags')->fetchColumn();
        $comments = (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
        @unlink($dbFile);

        $this->assertSame(2, $users);
        $this->assertSame(3, $todos);
        $this->assertSame(1, $categories);
        $this->assertSame(1, $tags);
        $this->assertSame(1, $comments);
    }

    public function testStartDoesNotReseedPopulatedDb(): void
    {
        $dbFile = $this->tempDb();
        $seed = new \PDO('sqlite:' . $dbFile);
        $seed->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT)');
        $seed->exec("INSERT INTO users (username) VALUES ('alice')");
        unset($seed);

        Bootstrap::start(['db_file' => $dbFile]);

        $pdo = new \PDO('sqlite:' . $dbFile);
        $users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        @unlink($dbFile);

        $this->assertSame(1, $users);
    }

    public function testStartDefaultsToLegacyTodoSiteName(): void
    {
        $dbFile = $this->tempDb();

        $app = Bootstrap::start(['db_file' => $dbFile]);
        @unlink($dbFile);

        $this->assertSame('Legacy Todo', $app->siteName());
    }

    public function testStartHonoursCustomSiteName(): void
    {
        $dbFile = $this->tempDb();

        $app = Bootstrap::start(['db_file' => $dbFile, 'siteName' => 'Custom Site']);
        @unlink($dbFile);

        $this->assertSame('Custom Site', $app->siteName());
    }

    private function tempDb(): string
    {
        return tempnam(sys_get_temp_dir(), 'legacy-todo-') . '.sqlite';
    }
}
