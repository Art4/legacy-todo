<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";

class Page
{
    /** @var Bootstrap */
    private $app;

    public function __construct(Bootstrap $app)
    {
        $this->app = $app;
    }

    /** @param mixed $value */
    public function text($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }

    /** @param mixed $value */
    public function attr($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }

    public function header(): string
    {
        $siteName = $this->app->siteName();
        $out = "<html><head><title>" . $siteName . " - " . $siteName . "</title>\n";
        $out .= "<style>body{font-family:Arial}</style>\n";
        $out .= "</head>\n";
        $out .= "<body>\n";
        $out .= "<div class=\"header\">\n";
        $out .= "<h2>" . $siteName . "</h2>\n";
        $user = $this->app->auth()->currentUser();
        if ($user != null && $user["username"] != "") {
            $out .= "<p>Eingeloggt als " . $this->text($user["username"]) . "</p>\n";
        }
        $out .= "</div>\n";

        return $out;
    }

    public function footer(): string
    {
        return "<div class=\"footer\">\n"
            . "<p>&copy; 2026 LegacyTodo - Version 0.1</p>\n"
            . "</div>\n"
            . "</body></html>\n";
    }

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function login(array $post)
    {
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
        $siteName = $this->text($this->app->siteName());
        $out .= "<html><head><title>Login - " . $siteName . "</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>Login</h1>\n";
        if ($msg != "") {
            $out .= "<p>" . $this->text($msg) . "</p>\n";
        }
        $out .= "<form method='post'>\n";
        $out .= "<input name='username' placeholder='Username' value='" . $this->attr($post["username"] ?? "") . "'>\n";
        $out .= "<input name='password' type='password' placeholder='Password'>\n";
        $out .= "<input type='submit' name='login' value='Login'>\n";
        $out .= "</form>\n";
        $out .= "<h2>Registrieren</h2>\n";
        $out .= "<form method=\"post\">\n";
        $out .= "<input name=\"username\" placeholder=\"Username\">\n";
        $out .= "<input name=\"password\" type=\"password\" placeholder=\"Password\">\n";
        $out .= "<input name=\"email\" placeholder=\"Email\" value=\"" . $this->attr($post["email"] ?? "") . "\">\n";
        $out .= "<input type=\"submit\" name=\"register\" value=\"Registrieren\">\n";
        $out .= "</form>\n";
        $out .= "</body></html>\n";

        return $out;
    }

    /**
     * @param array<string, mixed> $get
     * @return string
     */
    public function deleteTodo(int $id, array $get)
    {
        $auth = $this->app->auth();
        $auth->requireLogin();
        if (!$auth->canManage($id)) {
            echo "Keine Berechtigung";
            exit;
        }
        if (($get["confirm"] ?? "") == "1") {
            $this->app->todos()->archive($id);
            header("Location: index.php");
            exit;
        }
        $t = $this->app->todos()->find($id);
        $out = "<html><body>\n";
        $out .= "<h1>Löschen?</h1>\n";
        $out .= "<p>" . $this->text($t["title"]) . " wirklich archivieren?</p>\n";
        $out .= "<a href=\"deletetodo.php?id=" . $this->attr($id) . "&confirm=1\">Ja</a> | <a href=\"index.php\">Nein</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}
