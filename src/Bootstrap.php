<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/Auth.php";
require_once __DIR__ . "/Csrf.php";
require_once __DIR__ . "/Dashboard.php";
require_once __DIR__ . "/DeleteTodoHandler.php";
require_once __DIR__ . "/Layout.php";
require_once __DIR__ . "/LoginHandler.php";
require_once __DIR__ . "/Page.php";
require_once __DIR__ . "/Todos.php";
require_once __DIR__ . "/Uploads.php";
require_once __DIR__ . "/Users.php";
require_once __DIR__ . "/TodoActivity.php";
require_once __DIR__ . "/Taxonomy.php";

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
        self::createSchema($pdo);
        self::seedIfEmpty($pdo);

        return new self($pdo, $_SESSION, $config['siteName'] ?? 'Legacy Todo');
    }

    /** @param string $dbFile */
    private static function connect($dbFile): \PDO
    {
        $pdo = new \PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        @chmod($dbFile, 0666);
        @chmod(dirname($dbFile), 0777);

        return $pdo;
    }

    private static function createSchema(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)");
        $pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)");
        $pdo->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec("CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)");
        $pdo->exec('CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)');
    }

    private static function seedIfEmpty(\PDO $pdo): void
    {
        $cnt = $pdo->query("SELECT COUNT(*) as c FROM users")->fetch(\PDO::FETCH_ASSOC);
        if ($cnt['c'] == 0) {
            $pdo->exec("INSERT INTO users (username,password,role,email,created_at) VALUES ('admin','" . password_hash('admin123', PASSWORD_BCRYPT) . "','admin','admin@example.com','2026-01-01')");
            $pdo->exec("INSERT INTO users (username,password,role,email,created_at) VALUES ('user','" . password_hash('user123', PASSWORD_BCRYPT) . "','user','user@example.com','2026-01-02')");
            $pdo->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (1,'Erstes Todo','Beschreibung 1','open',1,'2026-12-31',0,'2026-01-10')");
            $pdo->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (2,'Zweites Todo','Noch was','done',2,'2026-11-01',0,'2026-01-11')");
            $pdo->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (1,'Archiviertes','Altes erledigtes','done',3,'2026-01-01',1,'2026-01-05')");
            $pdo->exec("INSERT INTO categories (name,user_id) VALUES ('Allgemein',1)");
            $pdo->exec("INSERT INTO tags (name) VALUES ('wichtig')");
            $pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (1,2,'Kommentar 1','2026-01-12')");
        }
    }

    /** @return string */
    public function siteName()
    {
        return $this->siteName;
    }

    public function auth(): Auth
    {
        if ($this->auth === null) {
            $this->auth = new Auth($this->pdo, $this->session);
        }

        return $this->auth;
    }

    public function todos(): Todos
    {
        if ($this->todos === null) {
            $this->todos = new Todos($this->pdo);
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

    /**
     * @return array<string, mixed>|null
     */
    public function currentUser()
    {
        return $this->auth()->currentUser();
    }
}
