<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";
require_once __DIR__ . "/Csrf.php";
require_once __DIR__ . "/Layout.php";

/**
 * Single responsible module: owns the user, category and tag administration
 * request flow and markup behind the page seam (issue #209).
 */
class AdminHandler
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
    public function handle(array $post)
    {
        $this->csrf->guard($post);
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
        $siteName = $this->layout->text($this->app->siteName());
        $out = "<html><head><title>Admin - " . $siteName . "</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>Admin</h1>\n";
        $out .= "<h2>Benutzer</h2>\n";
        $out .= "<ul>\n";
        foreach ($userRows as $u) {
            $out .= "<li>" . $this->layout->text($u["username"]) . " - " . $this->layout->text($u["role"]) . " - " . $this->layout->text($u["email"]) . "</li>\n";
        }
        $out .= "</ul>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name=\"username\" placeholder=\"Username\">\n";
        $out .= "<input name=\"password\" placeholder=\"Password\">\n";
        $out .= "<select name=\"role\"><option value=\"user\">user</option><option value=\"admin\">admin</option></select>\n";
        $out .= "<input type=\"submit\" name=\"add_user\" value=\"User anlegen\">\n";
        $out .= "</form>\n";
        $out .= "<h2>Kategorien</h2>\n";
        $out .= "<ul>\n";
        foreach ($cats as $c) {
            $out .= "<li>" . $this->layout->text($c["name"]) . "</li>\n";
        }
        $out .= "</ul>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name=\"kategorie\" placeholder=\"Kategorie (kategorie)\">\n";
        $out .= "<input name=\"cat\" placeholder=\"cat\">\n";
        $out .= "<input name=\"category\" placeholder=\"category\">\n";
        $out .= "<input type=\"submit\" name=\"add_cat\" value=\"Kategorie\">\n";
        $out .= "</form>\n";
        $out .= "<h2>Tags</h2>\n";
        $out .= "<ul>\n";
        foreach ($tags as $t) {
            $out .= "<li>" . $this->layout->text($t["name"]) . "</li>\n";
        }
        $out .= "</ul>\n";
        $out .= "<form method=\"post\">\n";
        $out .= $this->csrf->field() . "\n";
        $out .= "<input name=\"tag\" placeholder=\"Tag\">\n";
        $out .= "<input type=\"submit\" name=\"add_tag\" value=\"Tag\">\n";
        $out .= "</form>\n";
        $out .= "<a href=\"index.php\">Zurück</a>\n";
        $out .= "</body></html>\n";

        return $out;
    }
}
