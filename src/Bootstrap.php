<?php

namespace Art4\LegacyTodo;

class Bootstrap
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

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

    public function __construct(\PDO $pdo, &$session = null)
    {
        $this->pdo = $pdo;
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . "/../config.php";
        require_once __DIR__ . "/../db.php";
        require_once __DIR__ . "/../functions.php";

        return new self(getDb(), $_SESSION);
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