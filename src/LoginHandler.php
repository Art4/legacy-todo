<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the login + register request flow and
 * markup behind the page seam (issue #209).
 */
class LoginHandler
{
    /** @var Bootstrap */
    private $app;

    /** @var Csrf */
    private $csrf;

    /** @var Layout */
    private $layout;

    public function __construct(Bootstrap $app)
    {
        $this->app = $app;
        $this->layout = $app->layout();
        $this->csrf = $app->csrf();
    }

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function handle(array $post)
    {
        $this->csrf->guard($post);
        $auth = $this->app->auth();
        $users = $this->app->users();
        $out = "";
        $msg = "";
        if (!empty($post["login"])) {
            $u = $post["username"] ?? "";
            $p = $post["password"] ?? "";
            if ($auth->login($u, $p)) {
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
            $r = $users->register($u, $p, $email);
            if ($r == true) {
                $msg = "Registriert";
            } else {
                $msg = "Fehler: " . $r;
                $out .= $msg;
            }
        }
        $siteName = $this->layout->text($this->app->siteName());
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
