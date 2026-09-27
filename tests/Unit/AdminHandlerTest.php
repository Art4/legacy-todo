<?php

declare(strict_types=1);

use Art4\LegacyTodo\AdminHandler;
use Art4\LegacyTodo\Auth;
use Art4\LegacyTodo\Csrf;
use Art4\LegacyTodo\Installer;
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
        (new Installer($this->pdo))->createSchema();
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

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_cat" => "Kategorie", "kategorie" => ""]);

        $this->assertStringContainsString('Name fehlt', $output);
    }

    public function testAdminAddCategoryUsesKategorieKeyWhenProvided(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_cat" => "Kategorie", "kategorie" => "Freizeit"]);

        $this->assertStringContainsString('<li>Freizeit</li>', $output);
    }

    public function testAdminAddCategoryIgnoresLegacyCatAndCategoryKeys(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_cat" => "Kategorie", "cat" => "Arbeit", "category" => "Privat"]);

        $this->assertStringContainsString('Name fehlt', $output);
        $this->assertStringNotContainsString('<li>Arbeit</li>', $output);
        $this->assertStringNotContainsString('<li>Privat</li>', $output);
    }

    public function testAdminAddCategoryStorageFailureRendersFailureMessage(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $failingPdo = new \PDO('sqlite::memory:');
        $failingPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        (new Installer($failingPdo))->createSchema();
        $failingPdo->exec('PRAGMA query_only = 1');
        $failingTaxonomy = new Taxonomy($failingPdo);
        $users = new Users($this->pdo);
        $todos = new Todos($this->pdo, $failingTaxonomy);
        $auth = new Auth($users, $todos, $this->session);
        $layout = new Layout('Legacy Todo', $auth);
        $handler = new AdminHandler($auth, $users, $failingTaxonomy, new Csrf($auth, $layout), $layout, 'Legacy Todo');

        $output = $handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_cat" => "Kategorie", "kategorie" => "Freizeit"]);

        $this->assertStringContainsString('Anlegen fehlgeschlagen', $output);
    }

    public function testAdminAddUserStorageFailureRendersFailureMessage(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $failingPdo = new \PDO('sqlite::memory:');
        $failingPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        (new Installer($failingPdo))->createSchema();
        $failingPdo->exec('PRAGMA query_only = 1');
        $failingUsers = new Users($failingPdo);
        $taxonomy = new Taxonomy($this->pdo);
        $todos = new Todos($this->pdo, $taxonomy);
        $auth = new Auth($failingUsers, $todos, $this->session);
        $layout = new Layout('Legacy Todo', $auth);
        $handler = new AdminHandler($auth, $failingUsers, $taxonomy, new Csrf($auth, $layout), $layout, 'Legacy Todo');

        $output = $handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_user" => "User anlegen", "username" => "newbob", "password" => "pw123", "role" => "user"]);

        $this->assertStringContainsString('Anlegen fehlgeschlagen', $output);
        $this->assertStringNotContainsString('newbob', $output);
    }

    public function testAdminAddTagCreatesTagAppearingInList(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_tag" => "Tag", "tag" => "neu"]);

        $this->assertStringContainsString('<li>neu</li>', $output);
    }

    public function testAdminAddTagStorageFailureRendersFailureMessage(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $failingPdo = new \PDO('sqlite::memory:');
        $failingPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        (new Installer($failingPdo))->createSchema();
        $failingPdo->exec('PRAGMA query_only = 1');
        $failingTaxonomy = new Taxonomy($failingPdo);
        $users = new Users($this->pdo);
        $todos = new Todos($this->pdo, $failingTaxonomy);
        $auth = new Auth($users, $todos, $this->session);
        $layout = new Layout('Legacy Todo', $auth);
        $handler = new AdminHandler($auth, $users, $failingTaxonomy, new Csrf($auth, $layout), $layout, 'Legacy Todo');

        $output = $handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_tag" => "Tag", "tag" => "neu"]);

        $this->assertStringContainsString('Anlegen fehlgeschlagen', $output);
    }

    public function testAdminAddTagWithEmptyNameRendersFailureMessage(): void
    {
        $this->session['user_id'] = 1;
        $this->session['username'] = 'alice';
        $this->session['role'] = 'admin';

        $output = $this->handler->handle(["_csrf_token" => $this->session['csrf_token'], "add_tag" => "Tag", "tag" => ""]);

        $this->assertStringContainsString('Anlegen fehlgeschlagen', $output);
        $this->assertStringNotContainsString('<li></li>', $output);
    }

    public function testAdminDeniesNonAdminWithComposed403(): void
    {
        $this->session['user_id'] = 9;
        $this->session['username'] = 'eve';
        $this->session['role'] = 'user';

        $output = $this->handler->handle([]);

        $this->assertStringContainsString("Keine Rechte", $output);
        $this->assertStringContainsString("<title>Keine Rechte</title>", $output);
        $this->assertStringContainsString("</body></html>", $output);
    }
}
