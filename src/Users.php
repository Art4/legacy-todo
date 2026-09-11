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
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=?");
        $stmt->execute([$username]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row == false) {
            return null;
        }

        $hash = $row["password"];
        if (password_verify($password, $hash)) {
            if (password_needs_rehash($hash, PASSWORD_BCRYPT)) {
                $this->updatePassword((int) $row["id"], $password);
            }

            return $row;
        }

        if (preg_match('/^[a-f0-9]{32}$/i', (string) $hash) === 1 && hash_equals($hash, md5($password))) {
            $this->updatePassword((int) $row["id"], $password);

            return $row;
        }

        return null;
    }

    private function updatePassword($id, $password): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
    }

    /**
     * @return RegistrationResult
     */
    public function register($u, $p, $email)
    {
        if ($u == "" || $p == "") {
            return RegistrationResult::invalidInput();
        }
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=?");
        $stmt->execute([$u]);
        $exists = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($exists != null) {
            return RegistrationResult::usernameTaken();
        }
        $hash = password_hash($p, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username,password,role,email,created_at) VALUES (?,?,'user',?,?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$u, $hash, $email, date("Y-m-d")]);
        } catch (\Exception $e) {
            return RegistrationResult::storageFailure();
        }

        return RegistrationResult::registered();
    }

    /** @return bool */
    public function create($username, $password, $role)
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
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
