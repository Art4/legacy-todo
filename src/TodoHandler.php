<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the detail, comment and assignment request
 * flow and markup behind the page seam (issue #209).
 */
class TodoHandler
{
    /** @var Auth */
    private $auth;

    /** @var Todos */
    private $todos;

    /** @var TodoActivity */
    private $todoActivity;

    /** @var Users */
    private $users;

    /** @var Csrf */
    private $csrf;

    /** @var Layout */
    private $layout;

    public function __construct(Auth $auth, Todos $todos, TodoActivity $todoActivity, Users $users, Csrf $csrf, Layout $layout)
    {
        $this->auth = $auth;
        $this->todos = $todos;
        $this->todoActivity = $todoActivity;
        $this->users = $users;
        $this->csrf = $csrf;
        $this->layout = $layout;
    }

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @return string|null
     */
    public function handle(int $id, array $get, array $post)
    {
        $this->csrf->guard($post);
        $this->auth->requireLogin();
        $t = $this->todos->find($id);
        if ($t == null) {
            echo "Not found";
            exit;
        }
        if (!empty($post["add_comment"])) {
            $body = $post["body"] ?? "";
            $uid = $this->auth->currentUser()["user_id"];
            $this->todoActivity->addComment($id, $uid, $body);
            header("Location: todo.php?id=" . $id);
            exit;
        }
        if (!empty($post["assign"])) {
            $assignee = $post["assignee"] ?? "";
            $this->todoActivity->assign($id, $assignee, $this->auth->currentUser()["user_id"]);
            if (($get["next"] ?? "") != "") {
                $this->auth->redirect("todo.php?id=" . $id, $get["next"]);
            }
        }
        if (!empty($get["del_comment"])) {
            $comment = $this->todoActivity->findComment($get["del_comment"]);
            if ($comment !== null && $this->auth->canDeleteComment($comment)) {
                $this->todoActivity->removeComment($get["del_comment"]);
            }
        }
        $comments = $this->todoActivity->commentsForTodo($id);
        $assigns = $this->todoActivity->assignmentsForTodo($id);

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
        $out = $this->layout->header("Todo - " . $t["title"]);
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
        foreach ($this->users->listAll() as $u) {
            $out .= "<option value='" . $this->layout->attr($u["id"]) . "'>" . $this->layout->text($u["username"]) . "</option>\n";
        }
        $out .= "</select>\n";
        $out .= "<input type=\"submit\" name=\"assign\" value=\"Zuweisen\">\n";
        $out .= "</form>\n";
        $out .= "<a href=\"edittodo.php?id=" . $this->layout->attr($id) . "\">Bearbeiten</a> | <a href=\"deletetodo.php?id=" . $this->layout->attr($id) . "\">Löschen</a> | <a href=\"index.php\">Zurück</a>\n";

        return $out . $this->layout->footer();
    }
}
