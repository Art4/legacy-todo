<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the user, category and tag administration
 * request flow and markup behind the page seam (issue #209).
 */
class AdminHandler
{
    /** @var Auth */
    private $auth;

    /** @var Users */
    private $users;

    /** @var Taxonomy */
    private $taxonomy;

    /** @var Csrf */
    private $csrf;

    /** @var Layout */
    private $layout;

    /** @var string */
    private $siteName;

    public function __construct(Auth $auth, Users $users, Taxonomy $taxonomy, Csrf $csrf, Layout $layout, string $siteName)
    {
        $this->auth = $auth;
        $this->users = $users;
        $this->taxonomy = $taxonomy;
        $this->csrf = $csrf;
        $this->layout = $layout;
        $this->siteName = $siteName;
    }

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function handle(array $post)
    {
        $this->csrf->guard($post);
        $this->auth->requireLogin();
        if (!$this->auth->requireRole("admin")) {
            return $this->layout->errorPage(403, "Keine Rechte");
        }
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
                $this->taxonomy->createCategory($name, $this->auth->currentUser()["user_id"]);
            }
        }
        if (!empty($post["add_user"])) {
            $u = $post["username"] ?? "";
            $p = $post["password"] ?? "";
            $role = $post["role"] ?? "";
            $this->users->create($u, $p, $role);
        }
        if (!empty($post["add_tag"])) {
            $this->taxonomy->createTag($post["tag"] ?? "");
        }
        $userRows = $this->users->listAll();
        $cats = $this->taxonomy->listCategories();
        $tags = $this->taxonomy->listTags();

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
        $out = $this->layout->header("Admin - " . $this->siteName);
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
        $out .= $this->layout->footer();

        return $out;
    }
}
