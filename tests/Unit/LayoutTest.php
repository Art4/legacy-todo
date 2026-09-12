<?php

declare(strict_types=1);

use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Layout;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Users;

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
        $auth = new Auth(new Users($pdo), new Todos($pdo, new Taxonomy($pdo)), $this->session);
        $this->layout = new Layout('Legacy Todo', $auth);
    }

    public function testTextEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $this->layout->text('<script>alert("x")</script>'));
    }

    public function testAttrEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame('&lt;i&gt;&amp;&quot;q&quot;&lt;/i&gt;', $this->layout->attr('<i>&"q"</i>'));
    }

    public function testHeaderRendersSiteNameFromConstructor(): void
    {
        $output = $this->layout->header();

        $this->assertStringContainsString('<title>Legacy Todo - Legacy Todo</title>', $output);
        $this->assertStringContainsString('<h2>Legacy Todo</h2>', $output);
    }

    public function testHeaderRendersGivenPageTitle(): void
    {
        $output = $this->layout->header('Neues Todo');

        $this->assertStringContainsString('<title>Neues Todo</title>', $output);
    }

    public function testHeaderEscapesGivenPageTitle(): void
    {
        $output = $this->layout->header('<b>Admin - Legacy Todo</b>');

        $this->assertStringNotContainsString('<title><b>', $output);
        $this->assertStringContainsString('<title>&lt;b&gt;Admin - Legacy Todo&lt;/b&gt;</title>', $output);
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

    public function testErrorPageSetsHttpStatusCode(): void
    {
        $this->layout->errorPage(403, 'Keine Rechte');

        $this->assertSame(403, http_response_code());
    }

    public function testErrorPageRendersMessageWithinChrome(): void
    {
        $output = $this->layout->errorPage(404, 'Not found');

        $this->assertStringContainsString('<title>Not found</title>', $output);
        $this->assertStringContainsString('<h1>Not found</h1>', $output);
        $this->assertStringContainsString('</body></html>', $output);
    }

    public function testErrorPageEscapesMessage(): void
    {
        $output = $this->layout->errorPage(403, '<script>alert("x")</script>');

        $this->assertStringNotContainsString('<h1><script>', $output);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', $output);
    }
}
