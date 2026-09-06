<?php

namespace Art4\LegacyTodo;

class Users
{
    /** @var \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return array<string, mixed>|null */
    public function findById($id)
    {
        $row = $this->pdo->query("SELECT * FROM users WHERE id=" . $id)->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }

    /** @return array<string, mixed>|null */
    public function findByUsername($username)
    {
        $row = $this->pdo->query("SELECT * FROM users WHERE username='" . $username . "'")->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }

    /** @return array<string, mixed>|null */
    public function authenticate($username, $password)
    {
        $row = $this->pdo->query("SELECT * FROM users WHERE username='" . $username . "' AND password='" . md5($password) . "'")->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }
}