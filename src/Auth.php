<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Users.php";
require_once __DIR__ . "/Todos.php";

class Auth
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    /** @var Users|null */
    private $users;

    /** @var Todos|null */
    private $todos;

    public function __construct(\PDO $pdo, &$session = null)
    {
        $this->pdo = $pdo;
        if ($session === null) {
            $this->session = &$_SESSION;
        } else {
            $this->session = &$session;
        }
    }

    private function users(): Users
    {
        if ($this->users === null) {
            $this->users = new Users($this->pdo);
        }

        return $this->users;
    }

    private function todos(): Todos
    {
        if ($this->todos === null) {
            $this->todos = new Todos($this->pdo);
        }

        return $this->todos;
    }

    /** @return array<string, mixed>|null */
    public function currentUser()
    {
        if (!$this->loggedIn()) {
            return null;
        }

        return [
            "user_id" => $this->session["user_id"],
            "username" => $this->session["username"] ?? "",
            "role" => $this->session["role"] ?? "",
        ];
    }

    /** @return bool */
    public function loggedIn()
    {
        $id = $this->session["user_id"] ?? null;

        return !($id == null || $id == "");
    }

    /** @return bool */
    public function login($username, $password)
    {
        $user = $this->users()->authenticate($username, $password);
        if ($user === null) {
            return false;
        }

        $this->session["user_id"] = $user["id"];
        $this->session["username"] = $user["username"];
        $this->session["role"] = $user["role"];

        return true;
    }

    public function logout()
    {
        $this->session = [];
    }
}