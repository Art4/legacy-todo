<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";
require_once __DIR__ . "/Layout.php";

/**
 * Single responsible module: owns the request→response pipeline behind the
 * page seam (issue #189). Its public interface is deliberately wide — six
 * endpoint methods plus the header/footer and escaping the front controllers
 * still compose against — and it orchestrates six complex page handlers, so
 * the resulting cyclomatic sum and public-method count exceed the PHPMD
 * threshold by design. PHPMD is a Signal producer here (see phpmd.xml.dist),
 * so these structural findings are suppressed rather than forcing a
 * shallower decomposition.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Page
{
    /** @var Bootstrap */
    private $app;

    /** @var Layout */
    private $layout;

    public function __construct(Bootstrap $app)
    {
        $this->app = $app;
        $this->layout = new Layout($app);
    }

    /** @param mixed $value */
    public function text($value): string
    {
        return $this->layout->text($value);
    }

    /** @param mixed $value */
    public function attr($value): string
    {
        return $this->layout->attr($value);
    }

    private function csrfField(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . $this->attr($this->app->auth()->csrfToken()) . '">';
    }

    /** @param array<string, mixed> $post */
    private function requireCsrfToken(array $post): void
    {
        if (!empty($post) && !$this->app->auth()->validateCsrfToken($post["_csrf_token"] ?? null)) {
            http_response_code(403);
            echo "CSRF token invalid";
            exit;
        }
    }

    public function header(): string
    {
        return $this->layout->header();
    }

    public function footer(): string
    {
        return $this->layout->footer();
    }

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function login(array $post)
    {
        $this->requireCsrfToken($post);
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
        $out .= $this->csrfField() . "\n";
        $out .= "<input name='username' placeholder='Username' value='" . $this->attr($post["username"] ?? "") . "'>\n";
        $out .= "<input name='password' type='password' placeholder='Password'>\n";
        $out .= "<input type='submit' name='login' value='Login'>\n";
        $out .= "</form>\n";
        $out .= "<h2>Registrieren</h2>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrfField() . "\n";
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function todo(int $id, array $get, array $post, array $files)
    {
        $this->requireCsrfToken($post);
        $auth = $this->app->auth();
        $todos = $this->app->todos();
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

        return $this->renderTodoDetail($t, $comments, $assigns, $id);
    }

    /**
     * @param array<string, mixed> $t
     * @param array<int, array<string, mixed>> $comments
     * @param array<int, array<string, mixed>> $assigns
     * @return string
     */
    private function renderTodoDetail(array $t, array $comments, array $assigns, int $id)
    {
        $users = $this->app->users();
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
        $out .= $this->csrfField() . "\n";
        $out .= "<textarea name=\"body\"></textarea>\n";
        $out .= "<input type=\"submit\" name=\"add_comment\" value=\"Kommentieren\">\n";
        $out .= "</form>\n";
        $out .= "<h3>Zuweisen</h3>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrfField() . "\n";
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
        $this->requireCsrfToken($post);
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
            $uploaded = $this->app->uploads()->store($files["upload"] ?? []);
            if ($uploaded !== null) {
                $out .= "Upload: " . $uploaded;
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
        $out .= $this->csrfField() . "\n";
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

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function editTodo(int $id, array $post)
    {
        $this->requireCsrfToken($post);
        $auth = $this->app->auth();
        $auth->requireLogin();
        if (!$auth->canManage($id)) {
            echo "Keine Berechtigung";
            exit;
        }
        $todos = $this->app->todos();
        $out = "";
        if (!empty($post["save"])) {
            $title = $post["title"] ?? "";
            $text = $post["text"] ?? "";
            $priority = $post["priority"] ?? "";
            $status = $post["status"] ?? "";
            if ($title == "") {
                $out .= "Titel erforderlich";
            } else {
                $todos->update($id, $title, $text, $priority, $status);
                $auth->redirect("todo.php?id=" . $id);
            }
        }
        $t = $todos->find($id);
        $out .= "<html><body>\n";
        $out .= "<h1>Todo bearbeiten</h1>\n";
        $out .= "<form method='post'>\n";
        $out .= $this->csrfField() . "\n";
        $out .= "<input name='title' value='" . $this->attr($t["title"] ?? "") . "'>\n";
        $out .= "<textarea name='text'>" . $this->text($t["text"] ?? "") . "</textarea>\n";
        $out .= "<select name='priority'>\n";
        if (($t["priority"] ?? null) == 1) {
            $out .= "<option selected value='1'>Hoch</option>\n";
        } else {
            $out .= "<option value='1'>Hoch</option>\n";
        }
        $out .= "<option value='2'>Normal</option>\n";
        $out .= "<option value='3'>Niedrig</option>\n";
        $out .= "</select>\n";
        $out .= "<select name='status'>\n";
        $out .= "<option value='open'>offen</option>\n";
        $out .= "<option value='done'>erledigt</option>\n";
        $out .= "</select>\n";
        $out .= "<input type='submit' name='save' value='Speichern'>\n";
        $out .= "</form>\n";
        $out .= "</body></html>\n";

        return $out;
    }

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function admin(array $post)
    {
        $this->requireCsrfToken($post);
        $auth = $this->app->auth();
        $auth->requireLogin();
        $auth->requireRole("admin");
        $users = $this->app->users();
        $taxonomy = $this->app->taxonomy();
        $out = "";
        if (!empty($post["add_cat"])) {
            $name = $post["kategorie"] ?? "";
            if (($post["cat"] ?? "") != "") {
                $name = $post["cat"];
            }
            if (($post["category"] ?? "") != "") {
                $name = $post["category"];
            }
            if ($name == "") {
                $out .= "Name fehlt";
            } else {
                $taxonomy->createCategory($name, $auth->currentUser()["user_id"]);
            }
        }
        if (!empty($post["add_user"])) {
            $u = $post["username"] ?? "";
            $p = $post["password"] ?? "";
            $role = $post["role"] ?? "";
            $users->create($u, $p, $role);
        }
        if (!empty($post["add_tag"])) {
            $taxonomy->createTag($post["tag"] ?? "");
        }
        $userRows = $users->listAll();
        $cats = $taxonomy->listCategories();
        $tags = $taxonomy->listTags();

        return $out . $this->renderAdmin($userRows, $cats, $tags);
    }

    /**
     * @param array<int, array<string, mixed>> $userRows
     * @param array<int, array<string, mixed>> $cats
     * @param array<int, array<string, mixed>> $tags
     * @return string
     */
    private function renderAdmin(array $userRows, array $cats, array $tags)
    {
        $siteName = $this->text($this->app->siteName());
        $out = "<html><head><title>Admin - " . $siteName . "</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>Admin</h1>\n";
        $out .= "<h2>Benutzer</h2>\n";
        $out .= "<ul>\n";
        foreach ($userRows as $u) {
            $out .= "<li>" . $this->text($u["username"]) . " - " . $this->text($u["role"]) . " - " . $this->text($u["email"]) . "</li>\n";
        }
        $out .= "</ul>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrfField() . "\n";
        $out .= "<input name=\"username\" placeholder=\"Username\">\n";
        $out .= "<input name=\"password\" placeholder=\"Password\">\n";
        $out .= "<select name=\"role\"><option value=\"user\">user</option><option value=\"admin\">admin</option></select>\n";
        $out .= "<input type=\"submit\" name=\"add_user\" value=\"User anlegen\">\n";
        $out .= "</form>\n";
        $out .= "<h2>Kategorien</h2>\n";
        $out .= "<ul>\n";
        foreach ($cats as $c) {
            $out .= "<li>" . $this->text($c["name"]) . "</li>\n";
        }
        $out .= "</ul>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrfField() . "\n";
        $out .= "<input name=\"kategorie\" placeholder=\"Kategorie (kategorie)\">\n";
        $out .= "<input name=\"cat\" placeholder=\"cat\">\n";
        $out .= "<input name=\"category\" placeholder=\"category\">\n";
        $out .= "<input type=\"submit\" name=\"add_cat\" value=\"Kategorie\">\n";
        $out .= "</form>\n";
        $out .= "<h2>Tags</h2>\n";
        $out .= "<ul>\n";
        foreach ($tags as $t) {
            $out .= "<li>" . $this->text($t["name"]) . "</li>\n";
        }
        $out .= "</ul>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrfField() . "\n";
        $out .= "<input name=\"tag\" placeholder=\"Tag\">\n";
        $out .= "<input type=\"submit\" name=\"add_tag\" value=\"Tag\">\n";
        $out .= "</form>\n";
        $out .= "<a href=\"index.php\">Zurück</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}
