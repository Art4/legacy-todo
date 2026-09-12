<?php

declare(strict_types=1);

use Art4\LegacyTodo\AdminHandler;
use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\Layout;
use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\Todos;
use Art4\LegacyTodo\Users;

final class AdminHandlerTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var AdminHandler */
    private $handler;

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
        $users = new Users($this->pdo);
        $taxonomy = new Taxonomy($this->pdo);
        $todos = new Todos($this->pdo, $taxonomy);
        $auth = new Auth($users, $todos, $this->session);
        $this->session['csrf_token'] = $auth->csrfToken();
        $layout = new Layout('Legacy Todo', $auth);
        $this->handler = new AdminHandler($auth, $users, $taxonomy, new Csrf($auth, $layout), $layout, 'Legacy Todo');
    }

    public function testPageRendersWithinLayoutChrome(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle([]);

        $this->assertStringContainsString('<html><head><title>Admin - Legacy Todo</title>', $output);
        $this->assertStringContainsString('<div class="header">', $output);
        $this->assertStringContainsString('<div class="footer">', $output);
        $this->assertStringNotContainsString('<html><body>', $output);
    }

    public function testAdminRendersUsersCategoriesAndTagsEscaped(): void
    {
        $this->pdo->exec("INSERT INTO users (id,username,password,role,email,created_at) VALUES (1,'<b>alice</b>','','admin','a@x.com','2026-01-01')");
        $this->pdo->exec("INSERT INTO categories (id,name,user_id) VALUES (1,'<i>Allgemein</i>',1)");
        $this->pdo->exec("INSERT INTO tags (id,name) VALUES (1,'<a>tag</a>')");
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle([]);

        $this->assertStringContainsString('<h1>Admin</h1>', $output);
        $this->assertStringContainsString('<li>&lt;b&gt;alice&lt;/b&gt; - admin - a@x.com</li>', $output);
        $this->assertStringContainsString('<li>&lt;i&gt;Allgemein&lt;/i&gt;</li>', $output);
        $this->assertStringContainsString('<li>&lt;a&gt;tag&lt;/a&gt;</li>', $output);
    }

    public function testAdminAddCategoryWithEmptyNameRendersNameFehlt(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_cat" => "Kategorie", "kategorie" => "", "cat" => "", "category" => ""]);

        $this->assertStringContainsString('Name fehlt', $output);
    }

    public function testAdminAddTagCreatesTagAppearingInList(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_tag" => "Tag", "tag" => "neu"]);

        $this->assertStringContainsString('<li>neu</li>', $output);
    }
}
