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

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @return string|null
     */
    public function todo(int $id, array $get, array $post, array $files)
    {
        $auth = $this->app->auth();
        $todos = $this->app->todos();
        $users = $this->app->users();
        $activity = $this->app->todoActivity();
        $auth->requireLogin();
        $t = $todos->find($id);
        if ($t == null) {
            echo "Not found";
            exit;
        }
        if (!empty($post["add_comment"])) {
            $body = $post["body"] ?? "";
            $uid = $auth->currentUser()["user_id"];
            $activity->addComment($id, $uid, $body);
            header("Location: todo.php?id=" . $id);
            exit;
        }
        if (!empty($post["assign"])) {
            $assignee = $post["assignee"] ?? "";
            $activity->assign($id, $assignee, $auth->currentUser()["user_id"]);
            if (($get["next"] ?? "") != "") {
                $auth->redirect("todo.php?id=" . $id);
            }
        }
        if (!empty($get["del_comment"])) {
            $activity->removeComment($get["del_comment"]);
        }
        $comments = $activity->commentsForTodo($id);
        $assigns = $activity->assignmentsForTodo($id);
        $out = "<html><head><title>Todo - " . $this->text($t["title"]) . "</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>" . $this->text($t["title"]) . "</h1>\n";
        $out .= "<p>" . $this->text($t["text"]) . "</p>\n";
        $out .= "<p>Status: " . $this->text($t["status"]) . " | Prio: " . $this->text($t["priority"]) . " | Fällig: " . $this->text($t["due_date"]) . "</p>\n";
        if (count($comments) > 0) {
            $out .= "<h3>Kommentare</h3>\n";
            foreach ($comments as $c) {
                $out .= "<p>" . $this->text($c["body"]) . " - User " . $this->text($c["user_id"]) . " <a href='todo.php?id=" . $this->attr($id) . "&del_comment=" . $this->attr($c["id"]) . "'>löschen</a></p>\n";
                $out .= "<small>" . $this->text($c["username"]) . "</small>\n";
            }
        }
        if (count($assigns) > 0) {
            $out .= "<h3>Zuweisungen</h3>\n";
            foreach ($assigns as $a) {
                $out .= "<p>" . $this->text($a["username"]) . "</p>\n";
            }
        }
        $out .= "<h3>Kommentar hinzufügen</h3>\n";
        $out .= "<form method=\"post\">\n";
        $out .= "<textarea name=\"body\"></textarea>\n";
        $out .= "<input type=\"submit\" name=\"add_comment\" value=\"Kommentieren\">\n";
        $out .= "</form>\n";
        $out .= "<h3>Zuweisen</h3>\n";
        $out .= "<form method=\"post\">\n";
        $out .= "<select name=\"assignee\">\n";
        foreach ($users->listAll() as $u) {
            $out .= "<option value='" . $this->attr($u["id"]) . "'>" . $this->text($u["username"]) . "</option>\n";
        }
        $out .= "</select>\n";
        $out .= "<input type=\"submit\" name=\"assign\" value=\"Zuweisen\">\n";
        $out .= "</form>\n";
        $out .= "<a href=\"edittodo.php?id=" . $this->attr($id) . "\">Bearbeiten</a> | <a href=\"deletetodo.php?id=" . $this->attr($id) . "\">Löschen</a> | <a href=\"index.php\">Zurück</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @return string|null
     */
    public function addTodo(array $post, array $files)
    {
        $auth = $this->app->auth();
        $auth->requireLogin("addtodo.php");
        $out = "";
        $msg = "";
        if (!empty($post["save"])) {
            $title = $post["title"] ?? "";
            $text = $post["text"] ?? "";
            $priority = $post["priority"] ?? "";
            $due = $post["due_date"] ?? "";
            $userId = $auth->currentUser()["user_id"];
            $file = $files["upload"]["name"] ?? "";
            if ($file != "") {
                $dest = dirname(__DIR__) . "/public/uploads/" . $file;
                @move_uploaded_file($files["upload"]["tmp_name"] ?? "", $dest);
                $out .= "Upload: " . $file;
            }
            if ($title == "") {
                $msg = "Titel erforderlich";
                $out .= $msg;
            } else {
                $r = $this->app->todos()->create($userId, $title, $text, $priority, $due);
                if ($r == true) {
                    header("Location: index.php");
                    exit;
                }
                $msg = "Fehler";
                $out .= $msg;
            }
        }
        $out .= "<html><head><title>Neues Todo</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>Todo erstellen</h1>\n";
        if ($msg !== "") {
            $out .= "<p>" . $this->text($msg) . "</p>\n";
        }
        $out .= "<form method='post' enctype='multipart/form-data'>\n";
        $out .= "<input name='title' placeholder='Titel' value='" . $this->attr($post["title"] ?? "") . "'>\n";
        $out .= "<textarea name='text'>" . $this->text($post["text"] ?? "") . "</textarea>\n";
        $out .= "<select name='priority'>\n";
        $out .= "<option value='1'>Hoch</option>\n";
        $out .= "<option value='2' selected>Normal</option>\n";
        $out .= "<option value='3'>Niedrig</option>\n";
        $out .= "</select>\n";
        $out .= "<select name='category_id'>\n";
        foreach ($this->app->taxonomy()->listCategories() as $c) {
            $out .= "<option value='" . $this->attr($c["id"]) . "'>" . $this->text($c["name"]) . "</option>\n";
        }
        $out .= "</select>\n";
        $out .= "<input name='due_date' type='date' value='" . $this->attr($post["due_date"] ?? "") . "'>\n";
        $out .= "<input type='file' name='upload'>\n";
        $out .= "<input type='submit' name='save' value='Speichern'>\n";
        $out .= "</form>\n";
        $out .= "<a href=\"index.php\">Zurück</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}
