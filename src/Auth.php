<?php

namespace Art4\LegacyTodo;

class Auth
{
    /** @var \PDO */
    private $pdo;

    /** @var array<string, mixed> */
    private $session;

    public function __construct(\PDO $pdo, &$session = null)
    {
        $this->pdo = $pdo;
        if ($session === null) {
            $this->session = &$_SESSION;
        } else {
            $this->session = &$session;
        }
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
}