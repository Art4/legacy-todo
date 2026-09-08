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
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }

    /** @return array<string, mixed>|null */
    public function findByUsername($username)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=?");
        $stmt->execute([$username]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }

    /** @return array<string, mixed>|null */
    public function authenticate($username, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=? AND password=?");
        $stmt->execute([$username, md5($password)]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        return $row;
    }

    /** @return bool|string */
    public function register($u, $p, $email)
    {
        if ($u == "" || $p == "") {
            return "Titel fehlt?";
        }
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=?");
        $stmt->execute([$u]);
        $exists = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($exists != null) {
            return "exists";
        }
        $hash = md5($p);
        $sql = "INSERT INTO users (username,password,role,email,created_at) VALUES (?,?,'user',?,?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$u, $hash, $email, date("Y-m-d")]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /** @return bool */
    public function create($username, $password, $role)
    {
        $hash = md5($password);
        $sql = "INSERT INTO users (username,password,role,email,created_at) VALUES (?,?,?,?,?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$username, $hash, $role, $username . "@example.com", date("Y-m-d")]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /** @return array<int, array<string, mixed>> */
    public function listAll()
    {
        return $this->pdo->query("SELECT * FROM users")->fetchAll(\PDO::FETCH_ASSOC);
    }
}