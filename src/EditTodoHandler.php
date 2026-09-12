<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the edit request flow and markup behind
 * the page seam (issue #209).
 */
class EditTodoHandler
{
    /** @var Auth */
    private $auth;

    /** @var Todos */
    private $todos;

    /** @var Csrf */
    private $csrf;

    /** @var Layout */
    private $layout;

    public function __construct(Auth $auth, Todos $todos, Csrf $csrf, Layout $layout)
    {
        $this->auth = $auth;
        $this->todos = $todos;
        $this->csrf = $csrf;
        $this->layout = $layout;
    }

    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @return string
     */
    public function handle(int $id, array $get, array $post)
    {
        $this->csrf->guard($post);
        $this->auth->requireLogin();
        if (!$this->auth->requireManage($id)) {
            return $this->layout->errorPage(403, "Keine Berechtigung");
        }
        $out = "";
        if (!empty($post["save"])) {
            $title = $post["title"] ?? "";
            $text = $post["text"] ?? "";
            $priority = $post["priority"] ?? "";
            $status = $post["status"] ?? "";
            if ($title == "") {
                $out .= "Titel erforderlich";
            } else {
                $this->todos->update($id, $title, $text, $priority, $status);
                $this->auth->redirect("todo.php?id=" . $id, $get["next"] ?? "");
            }
        }
        $t = $this->todos->find($id);
        $title = $t === null ? "" : $t->title();
        $text = $t === null ? "" : $t->text();
        $out .= $this->layout->header("Todo bearbeiten");
        $out .= "<h1>Todo bearbeiten</h1>\n";
        $out .= "<form method='post'>\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name='title' value='" . $this->layout->attr($title) . "'>\n";
        $out .= "<textarea name='text'>" . $this->layout->text($text) . "</textarea>\n";
        $out .= "<select name='priority'>\n";
        if ($t !== null && $t->priority() == 1) {
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
        $out .= $this->layout->footer();

        return $out;
    }
}
