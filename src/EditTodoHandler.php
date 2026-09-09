<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";
require_once __DIR__ . "/Csrf.php";
require_once __DIR__ . "/Layout.php";

/**
 * Single responsible module: owns the edit request flow and markup behind
 * the page seam (issue #209).
 */
class EditTodoHandler
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
     * @param array<string, mixed> $post
     * @return string
     */
    public function handle(int $id, array $post)
    {
        $this->csrf->guard($post);
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
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name='title' value='" . $this->layout->attr($t["title"] ?? "") . "'>\n";
        $out .= "<textarea name='text'>" . $this->layout->text($t["text"] ?? "") . "</textarea>\n";
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
}