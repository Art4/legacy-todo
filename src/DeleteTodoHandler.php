<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the delete/archive request flow and markup
 * behind the page seam (issue #209).
 */
class DeleteTodoHandler
{
    /** @var Auth */
    private $auth;

    /** @var Todos */
    private $todos;

    /** @var Layout */
    private $layout;

    public function __construct(Auth $auth, Todos $todos, Layout $layout)
    {
        $this->auth = $auth;
        $this->todos = $todos;
        $this->layout = $layout;
    }

    /**
     * @param array<string, mixed> $get
     * @return string
     */
    public function handle(int $id, array $get)
    {
        $this->auth->requireLogin();
        if (!$this->auth->requireManage($id)) {
            return $this->layout->errorPage(403, "Keine Berechtigung");
        }
        $msg = "";
        if (($get["confirm"] ?? "") == "1") {
            if ($this->todos->archive($id)) {
                $this->auth->redirect("index.php");
            }
            $msg = "Archivieren fehlgeschlagen";
        }
        $t = $this->todos->find($id);
        $out = $this->layout->header("Löschen?");
        $out .= "<h1>Löschen?</h1>\n";
        if ($msg !== "") {
            $out .= "<p>" . $this->layout->text($msg) . "</p>\n";
        }
        $out .= "<p>" . $this->layout->text($t === null ? "" : $t->title()) . " wirklich archivieren?</p>\n";
        $out .= "<a href=\"deletetodo.php?id=" . $this->layout->attr($id) . "&confirm=1\">Ja</a> | <a href=\"index.php\">Nein</a>\n";
        $out .= $this->layout->footer();

        return $out;
    }
}
