<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the login + register request flow and
 * markup behind the page seam (issue #209).
 */
class LoginHandler
{
    /** @var Auth */
    private $auth;

    /** @var Users */
    private $users;

    /** @var Csrf */
    private $csrf;

    /** @var Layout */
    private $layout;

    /** @var string */
    private $siteName;

    public function __construct(Auth $auth, Users $users, Csrf $csrf, Layout $layout, string $siteName)
    {
        $this->auth = $auth;
        $this->users = $users;
        $this->csrf = $csrf;
        $this->layout = $layout;
        $this->siteName = $siteName;
    }

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function handle(array $post)
    {
        $this->csrf->guard($post);
        $out = "";
        $msg = "";
        if (!empty($post["login"])) {
            $u = $post["username"] ?? "";
            $p = $post["password"] ?? "";
            if ($this->auth->login($u, $p)) {
                header("Location: index.php");
                exit;
            }
            $msg = "Login failed";
            $out .= $msg;
        }
        if (!empty($post["register"])) {
            $u = $post["username"] ?? "";
            $p = $post["password"] ?? "";
            $email = $post["email"] ?? "";
            $result = $this->users->register($u, $p, $email);
            if ($result->isRegistered()) {
                $msg = "Registriert";
            } elseif ($result->status() === RegistrationResult::STATUS_INVALID_INPUT) {
                $msg = "Fehler: Benutzername und Passwort sind erforderlich.";
            } elseif ($result->status() === RegistrationResult::STATUS_USERNAME_TAKEN) {
                $msg = "Fehler: Dieser Benutzername ist bereits vergeben.";
            } else {
                $msg = "Fehler: Registrierung fehlgeschlagen.";
            }
        }
        $siteName = $this->layout->text($this->siteName);
        $out .= "<html><head><title>Login - " . $siteName . "</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>Login</h1>\n";
        if ($msg != "") {
            $out .= "<p>" . $this->layout->text($msg) . "</p>\n";
        }
        $out .= "<form method='post'>\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name='username' placeholder='Username' value='" . $this->layout->attr($post["username"] ?? "") . "'>\n";
        $out .= "<input name='password' type='password' placeholder='Password'>\n";
        $out .= "<input type='submit' name='login' value='Login'>\n";
        $out .= "</form>\n";
        $out .= "<h2>Registrieren</h2>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name=\"username\" placeholder=\"Username\">\n";
        $out .= "<input name=\"password\" type=\"password\" placeholder=\"Password\">\n";
        $out .= "<input name=\"email\" placeholder=\"Email\" value=\"" . $this->layout->attr($post["email"] ?? "") . "\">\n";
        $out .= "<input type=\"submit\" name=\"register\" value=\"Registrieren\">\n";
        $out .= "</form>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}
