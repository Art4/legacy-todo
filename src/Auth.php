<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns login state, permission decisions, and the
 * CSRF token contract shared by every state-changing form. The public-method
 * count exceeds the PHPMD threshold by design (session reads/writes, identity,
 * roles, redirect, token issue + validation); PHPMD is a Signal producer here
 * (see phpmd.xml.dist), so it is suppressed rather than forcing a shallower
 * decomposition.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Auth
{
    /** @var array<string, mixed> */
    private $session;

    /** @var Users */
    private $users;

    /** @var Todos */
    private $todos;

    public function __construct(Users $users, Todos $todos, &$session = null)
    {
        $this->users = $users;
        $this->todos = $todos;
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

    /** @return bool */
    public function login($username, $password)
    {
        $user = $this->users->authenticate($username, $password);
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

    /** @param string|null $next */
    public function requireLogin($next = null)
    {
        if ($this->loggedIn()) {
            return;
        }

        $target = "login.php";
        if ($next != null) {
            $target = "login.php?next=" . $next;
        }
        header("Location: " . $target);
        exit;
    }

    public function requireRole($role)
    {
        if (($this->session["role"] ?? null) != $role) {
            echo "Keine Rechte";
            exit;
        }
    }

    /** @return bool */
    public function canManage($todoId)
    {
        if (($this->session["role"] ?? null) == "admin") {
            return true;
        }
        $userId = $this->session["user_id"] ?? null;
        if ($userId == null || $userId == "") {
            return false;
        }
        $found = $this->todos->find($todoId);

        return $found != null && $found["user_id"] == $userId;
    }

    public function redirect($url)
    {
        $next = $_GET["next"] ?? "";
        if ($next != "" && $this->isSafeInAppTarget($next)) {
            $url = $next;
        }
        header("Location: " . $url);
        exit;
    }

    private function isSafeInAppTarget(string $next): bool
    {
        if (strpos($next, "://") !== false) {
            return false;
        }
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.\-]*:/', $next) === 1) {
            return false;
        }
        if (substr($next, 0, 2) === "//") {
            return false;
        }
        if (strpos($next, "\\") !== false) {
            return false;
        }

        return true;
    }

    public function csrfToken(): string
    {
        if (empty($this->session["csrf_token"])) {
            $this->session["csrf_token"] = bin2hex(random_bytes(32));
        }

        return $this->session["csrf_token"];
    }

    /** @param string|null $token */
    public function validateCsrfToken($token): bool
    {
        return hash_equals($this->csrfToken(), $token ?? "");
    }
}
