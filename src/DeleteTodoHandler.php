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
        $this->auth->requireManage($id);
        if (($get["confirm"] ?? "") == "1") {
            $this->todos->archive($id);
            header("Location: index.php");
            exit;
        }
        $t = $this->todos->find($id);
        $out = $this->layout->header("Löschen?");
        $out .= "<h1>Löschen?</h1>\n";
        $out .= "<p>" . $this->layout->text($t["title"]) . " wirklich archivieren?</p>\n";
        $out .= "<a href=\"deletetodo.php?id=" . $this->layout->attr($id) . "&confirm=1\">Ja</a> | <a href=\"index.php\">Nein</a>\n";
        $out .= $this->layout->footer();

        return $out;
    }
}
