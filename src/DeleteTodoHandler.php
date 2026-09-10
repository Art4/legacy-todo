<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";

/**
 * Single responsible module: owns the delete/archive request flow and markup
 * behind the page seam (issue #209).
 */
class DeleteTodoHandler
{
    /** @var Bootstrap */
    private $app;

    /** @var Layout */
    private $layout;

    public function __construct(Bootstrap $app)
    {
        $this->app = $app;
        $this->layout = $app->layout();
    }

    /**
     * @param array<string, mixed> $get
     * @return string
     */
    public function handle(int $id, array $get)
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
        $out .= "<p>" . $this->layout->text($t["title"]) . " wirklich archivieren?</p>\n";
        $out .= "<a href=\"deletetodo.php?id=" . $this->layout->attr($id) . "&confirm=1\">Ja</a> | <a href=\"index.php\">Nein</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}
