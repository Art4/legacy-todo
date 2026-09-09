<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";
require_once __DIR__ . "/Csrf.php";
require_once __DIR__ . "/Layout.php";

/**
 * Single responsible module: owns the detail, comment and assignment request
 * flow and markup behind the page seam (issue #209).
 */
class TodoHandler
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
        $this->layout = new Layout($app);
        $this->csrf = new Csrf($app->auth(), $this->layout);
    }

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @return string|null
     */
    public function handle(int $id, array $get, array $post)
    {
        $this->csrf->guard($post);
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
        $out = "<html><head><title>Todo - " . $this->layout->text($t["title"]) . "</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>" . $this->layout->text($t["title"]) . "</h1>\n";
        $out .= "<p>" . $this->layout->text($t["text"]) . "</p>\n";
        $out .= "<p>Status: " . $this->layout->text($t["status"]) . " | Prio: " . $this->layout->text($t["priority"]) . " | Fällig: " . $this->layout->text($t["due_date"]) . "</p>\n";
        if (count($comments) > 0) {
            $out .= "<h3>Kommentare</h3>\n";
            foreach ($comments as $c) {
                $out .= "<p>" . $this->layout->text($c["body"]) . " - User " . $this->layout->text($c["user_id"]) . " <a href='todo.php?id=" . $this->layout->attr($id) . "&del_comment=" . $this->layout->attr($c["id"]) . "'>löschen</a></p>\n";
                $out .= "<small>" . $this->layout->text($c["username"]) . "</small>\n";
            }
        }
        if (count($assigns) > 0) {
            $out .= "<h3>Zuweisungen</h3>\n";
            foreach ($assigns as $a) {
                $out .= "<p>" . $this->layout->text($a["username"]) . "</p>\n";
            }
        }
        $out .= "<h3>Kommentar hinzufügen</h3>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<textarea name=\"body\"></textarea>\n";
        $out .= "<input type=\"submit\" name=\"add_comment\" value=\"Kommentieren\">\n";
        $out .= "</form>\n";
        $out .= "<h3>Zuweisen</h3>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<select name=\"assignee\">\n";
        foreach ($users->listAll() as $u) {
            $out .= "<option value='" . $this->layout->attr($u["id"]) . "'>" . $this->layout->text($u["username"]) . "</option>\n";
        }
        $out .= "</select>\n";
        $out .= "<input type=\"submit\" name=\"assign\" value=\"Zuweisen\">\n";
        $out .= "</form>\n";
        $out .= "<a href=\"edittodo.php?id=" . $this->layout->attr($id) . "\">Bearbeiten</a> | <a href=\"deletetodo.php?id=" . $this->layout->attr($id) . "\">Löschen</a> | <a href=\"index.php\">Zurück</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}