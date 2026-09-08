<?php

declare(strict_types=1);

use Art4\LegacyTodo\Bootstrap;
use Art4\LegacyTodo\Page;

final class PageTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var Bootstrap */
    private $app;

    /** @var Page */
    private $page;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)');
        $this->session = [];
        $this->app = new Bootstrap($this->pdo, $this->session, 'Legacy Todo');
        $this->page = new Page($this->app);
    }

    public function testTextEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $this->page->text('<script>alert("x")</script>'));
    }

    public function testAttrEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;i&gt;&amp;&quot;q&quot;&lt;/i&gt;', $this->page->attr('<i>&"q"</i>'));
    }

    public function testHeaderRendersSiteNameFromBootstrap(): void
    {
        $output = $this->page->header();

        $this->assertStringContainsString('<title>Legacy Todo - Legacy Todo</title>', $output);
        $this->assertStringContainsString('<h2>Legacy Todo</h2>', $output);
    }

    public function testHeaderRendersLoggedInUserFromAuthWhenLoggedIn(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'user';

        $output = $this->page->header();

        $this->assertStringContainsString('<p>Eingeloggt als alice</p>', $output);
    }

    public function testHeaderOmitsLoggedInUserWhenAnonymous(): void
    {
        $output = $this->page->header();

        $this->assertStringNotContainsString('Eingeloggt als', $output);
    }

    public function testFooterRendersVersion(): void
    {
        $output = $this->page->footer();

        $this->assertStringContainsString('<p>&copy; 2026 LegacyTodo - Version 0.1</p>', $output);
    }
}
