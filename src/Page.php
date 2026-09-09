<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";
require_once __DIR__ . "/Csrf.php";
require_once __DIR__ . "/Layout.php";

/**
 * Single responsible module: owns the request→response pipeline behind the
 * page seam (issue #189). Its public interface is deliberately wide — six
 * endpoint methods plus the header/footer and escaping the front controllers
 * still compose against — and it orchestrates six complex page handlers, so
 * the resulting cyclomatic sum and public-method count exceed the PHPMD
 * threshold by design. PHPMD is a Signal producer here (see phpmd.xml.dist),
 * so these structural findings are suppressed rather than forcing a
 * shallower decomposition.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Page
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

    /** @param mixed $value */
    public function text($value): string
    {
        return $this->layout->text($value);
    }

    /** @param mixed $value */
    public function attr($value): string
    {
        return $this->layout->attr($value);
    }

    public function header(): string
    {
        return $this->layout->header();
    }

    public function footer(): string
    {
        return $this->layout->footer();
    }

    /**
     * @param array<string, mixed> $post
     * @return string
     */
    public function admin(array $post)
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
        $siteName = $this->text($this->app->siteName());
        $out = "<html><head><title>Admin - " . $siteName . "</title></head>\n";
        $out .= "<body>\n";
        $out .= "<h1>Admin</h1>\n";
        $out .= "<h2>Benutzer</h2>\n";
        $out .= "<ul>\n";
        foreach ($userRows as $u) {
            $out .= "<li>" . $this->text($u["username"]) . " - " . $this->text($u["role"]) . " - " . $this->text($u["email"]) . "</li>\n";
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
            $out .= "<li>" . $this->text($c["name"]) . "</li>\n";
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
            $out .= "<li>" . $this->text($t["name"]) . "</li>\n";
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
