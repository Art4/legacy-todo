<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Single responsible module: owns the application composition root behind the
 * bootstrap seam. It is a module locator exposing one lazy accessor per owned
 * module (auth, todos, users, uploads, taxonomy, …), which is its entire
 * reason to exist, so the resulting public-method count exceeds the PHPMD
 * threshold by design. PHPMD is a Signal producer here (see phpmd.xml.dist),
 * so this structural finding is suppressed rather than forcing a shallower
 * decomposition.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Bootstrap
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var string */
    private $siteName;

    /** @var Auth|null */
    private $auth;

    /** @var Dashboard|null */
    private $dashboard;

    /** @var Todos|null */
    private $todos;

    /** @var Users|null */
    private $users;

    /** @var Uploads|null */
    private $uploads;

    /** @var TodoActivity|null */
    private $todoActivity;

    /** @var Taxonomy|null */
    private $taxonomy;

    /** @var Layout|null */
    private $layout;

    /** @var Csrf|null */
    private $csrf;

    /** @var string */
    private $uploadsDir;

    /** @param string|null $siteName */
    public function __construct(\PDO $pdo, &$session = null, $siteName = "Legacy Todo", $uploadsDir = null)
    {
        $this->pdo = $pdo;
        $this->siteName = $siteName;
        $this->uploadsDir = $uploadsDir ?? dirname(__DIR__) . '/public/uploads';
        if ($session === null) {
            $this->session = &$_SESSION;
        } else {
            $this->session = &$session;
        }
    }

    /**
     * @param array{db_file?: string, siteName?: string} $config
     * @return self
     */
    public static function start(array $config = [])
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        date_default_timezone_set('UTC');

        $pdo = self::connect($config['db_file'] ?? (getenv('LEGACY_TODO_DB_FILE') ?: dirname(__DIR__) . '/database.sqlite'));

        return new self($pdo, $_SESSION, $config['siteName'] ?? 'Legacy Todo');
    }

    /**
     * @param array{db_file?: string} $config
     */
    public static function install(array $config = []): void
    {
        $pdo = self::connect($config['db_file'] ?? (getenv('LEGACY_TODO_DB_FILE') ?: dirname(__DIR__) . '/database.sqlite'));
        $installer = new Installer($pdo);
        $installer->install();
    }

    /** @param string $dbFile */
    private static function connect($dbFile): \PDO
    {
        $pdo = new \PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /** @return string */
    public function siteName()
    {
        return $this->siteName;
    }

    public function auth(): Auth
    {
        if ($this->auth === null) {
            $this->auth = new Auth($this->users(), $this->todos(), $this->session);
        }

        return $this->auth;
    }

    public function todos(): Todos
    {
        if ($this->todos === null) {
            $this->todos = new Todos($this->pdo, $this->taxonomy());
        }

        return $this->todos;
    }

    public function dashboard(): Dashboard
    {
        if ($this->dashboard === null) {
            $this->dashboard = new Dashboard($this->todos());
        }

        return $this->dashboard;
    }

    public function users(): Users
    {
        if ($this->users === null) {
            $this->users = new Users($this->pdo);
        }

        return $this->users;
    }

    public function uploads(): Uploads
    {
        if ($this->uploads === null) {
            $this->uploads = new Uploads($this->uploadsDir);
        }

        return $this->uploads;
    }

    public function todoActivity(): TodoActivity
    {
        if ($this->todoActivity === null) {
            $this->todoActivity = new TodoActivity($this->pdo);
        }

        return $this->todoActivity;
    }

    public function taxonomy(): Taxonomy
    {
        if ($this->taxonomy === null) {
            $this->taxonomy = new Taxonomy($this->pdo);
        }

        return $this->taxonomy;
    }

    public function layout(): Layout
    {
        if ($this->layout === null) {
            $this->layout = new Layout($this->siteName, $this->auth());
        }

        return $this->layout;
    }

    public function csrf(): Csrf
    {
        if ($this->csrf === null) {
            $this->csrf = new Csrf($this->auth(), $this->layout());
        }

        return $this->csrf;
    }

    public function addTodoHandler(): AddTodoHandler
    {
        return new AddTodoHandler($this->auth(), $this->todos(), $this->taxonomy(), $this->uploads(), $this->csrf(), $this->layout());
    }

    public function todoHandler(): TodoHandler
    {
        return new TodoHandler($this);
    }

    public function loginHandler(): LoginHandler
    {
        return new LoginHandler($this->auth(), $this->users(), $this->csrf(), $this->layout(), $this->siteName);
    }

    public function adminHandler(): AdminHandler
    {
        return new AdminHandler($this->auth(), $this->users(), $this->taxonomy(), $this->csrf(), $this->layout(), $this->siteName);
    }

    public function deleteTodoHandler(): DeleteTodoHandler
    {
        return new DeleteTodoHandler($this->auth(), $this->todos(), $this->layout());
    }

    public function editTodoHandler(): EditTodoHandler
    {
        return new EditTodoHandler($this->auth(), $this->todos(), $this->csrf(), $this->layout());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentUser()
    {
        return $this->auth()->currentUser();
    }
}
