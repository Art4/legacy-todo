<?php

declare(strict_types=1);

use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Layout;

final class LayoutTest extends PHPUnit\Framework\TestCase
{
    /** @var array<string, mixed> */
    private $session;

    /** @var Layout */
    private $layout;

    protected function setUp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)');
        $this->session = [];
        $app = new Bootstrap($pdo, $this->session, 'Legacy Todo');
        $this->layout = new Layout($app);
    }

    public function testTextEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $this->layout->text('<script>alert("x")</script>'));
    }

    public function testAttrEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;i&gt;&amp;&quot;q&quot;&lt;/i&gt;', $this->layout->attr('<i>&"q"</i>'));
    }

    public function testHeaderRendersSiteNameFromBootstrap(): void
    {
        $output = $this->layout->header();

        $this->assertStringContainsString('<title>Legacy Todo - Legacy Todo</title>', $output);
        $this->assertStringContainsString('<h2>Legacy Todo</h2>', $output);
    }

    public function testHeaderRendersLoggedInUserFromAuthWhenLoggedIn(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'user';

        $output = $this->layout->header();

        $this->assertStringContainsString('<p>Eingeloggt als alice</p>', $output);
    }

    public function testHeaderOmitsLoggedInUserWhenAnonymous(): void
    {
        $output = $this->layout->header();

        $this->assertStringNotContainsString('Eingeloggt als', $output);
    }

    public function testFooterRendersVersion(): void
    {
        $output = $this->layout->footer();

        $this->assertStringContainsString('<p>&copy; 2026 LegacyTodo - Version 0.1</p>', $output);
    }
}
