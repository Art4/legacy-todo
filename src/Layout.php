<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Bootstrap.php";

/**
 * Single responsible module: owns the page chrome and the escaping surface
 * behind the page seam (issue #209) — text/attr escaping plus the header
 * and footer markup the front controllers compose against.
 */
class Layout
{
    /** @var Bootstrap */
    private $app;

    public function __construct(Bootstrap $app)
    {
        $this->app = $app;
    }

    /** @param mixed $value */
    public function text($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }

    /** @param mixed $value */
    public function attr($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }

    public function header(): string
    {
        $siteName = $this->app->siteName();
        $out = "<html><head><title>" . $siteName . " - " . $siteName . "</title>\n";
        $out .= "<style>body{font-family:Arial}</style>\n";
        $out .= "</head>\n";
        $out .= "<body>\n";
        $out .= "<div class=\"header\">\n";
        $out .= "<h2>" . $siteName . "</h2>\n";
        $user = $this->app->auth()->currentUser();
        if ($user != null && $user["username"] != "") {
            $out .= "<p>Eingeloggt als " . $this->text($user["username"]) . "</p>\n";
        }
        $out .= "</div>\n";

        return $out;
    }

    public function footer(): string
    {
        return "<div class=\"footer\">\n"
            . "<p>&copy; 2026 LegacyTodo - Version 0.1</p>\n"
            . "</div>\n"
            . "</body></html>\n";
    }
}