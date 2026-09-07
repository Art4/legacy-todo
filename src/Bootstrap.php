<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Auth.php";
require_once __DIR__ . "/Todos.php";
require_once __DIR__ . "/Users.php";
require_once __DIR__ . "/TodoActivity.php";
require_once __DIR__ . "/Taxonomy.php";

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

    /** @var Todos|null */
    private $todos;

    /** @var Users|null */
    private $users;

    /** @var TodoActivity|null */
    private $todoActivity;

    /** @var Taxonomy|null */
    private $taxonomy;

    /** @param string|null $siteName */
    public function __construct(\PDO $pdo, &$session = null, $siteName = "Legacy Todo")
    {
        $this->pdo = $pdo;
        $this->siteName = $siteName;
        if ($session === null) {
            $this->session = &$_SESSION;
        } else {
            $this->session = &$session;
        }
    }

    /**
     * @return self
     */
    public static function start()
    {
        global $db, $cfg, $site_name;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . "/../config.php";
        require_once __DIR__ . "/../db.php";
        require_once __DIR__ . "/../functions.php";

        $pdo = getDb();
        if ($pdo === null) {
            $pdo = new \PDO('sqlite:database.sqlite');
        }

        return new self($pdo, $_SESSION, $site_name);
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

    public function users(): Users
    {
        if ($this->users === null) {
            $this->users = new Users($this->pdo);
        }

        return $this->users;
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