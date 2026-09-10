<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";

/**
 * Single responsible module: owns the create + upload request flow and markup
 * behind the page seam (issue #209).
 */
class AddTodoHandler
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
        $this->layout = $app->layout();
        $this->csrf = $app->csrf();
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @return string|null
     */
    public function handle(array $post, array $files)
    {
        $this->csrf->guard($post);
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
            $out .= "<p>" . $this->layout->text($msg) . "</p>\n";
        }
        $out .= "<form method='post' enctype='multipart/form-data'>\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name='title' placeholder='Titel' value='" . $this->layout->attr($post["title"] ?? "") . "'>\n";
        $out .= "<textarea name='text'>" . $this->layout->text($post["text"] ?? "") . "</textarea>\n";
        $out .= "<select name='priority'>\n";
        $out .= "<option value='1'>Hoch</option>\n";
        $out .= "<option value='2' selected>Normal</option>\n";
        $out .= "<option value='3'>Niedrig</option>\n";
        $out .= "</select>\n";
        $out .= "<select name='category_id'>\n";
        foreach ($this->app->taxonomy()->listCategories() as $c) {
            $out .= "<option value='" . $this->layout->attr($c["id"]) . "'>" . $this->layout->text($c["name"]) . "</option>\n";
        }
        $out .= "</select>\n";
        $out .= "<input name='due_date' type='date' value='" . $this->layout->attr($post["due_date"] ?? "") . "'>\n";
        $out .= "<input type='file' name='upload'>\n";
        $out .= "<input type='submit' name='save' value='Speichern'>\n";
        $out .= "</form>\n";
        $out .= "<a href=\"index.php\">Zurück</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}
