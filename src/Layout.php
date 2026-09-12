<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the page chrome, the escaping surface
 * and the error/abort response behind the page seam (issue #209) — text/attr
 * escaping plus the header and footer markup the front controllers compose
 * against, and the status-carrying error page every denial path renders.
 */
class Layout
{
    /** @var string */
    private $siteName;

    /** @var Auth */
    private $auth;

    public function __construct(string $siteName, Auth $auth)
    {
        $this->siteName = $siteName;
        $this->auth = $auth;
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

    public function header(?string $title = null): string
    {
        $siteName = $this->siteName;
        $pageTitle = $title === null ? $siteName . " - " . $siteName : $this->text($title);
        $out = "<html><head><title>" . $pageTitle . "</title>\n";
        $out .= "<style>body{font-family:Arial}</style>\n";
        $out .= "</head>\n";
        $out .= "<body>\n";
        $out .= "<div class=\"header\">\n";
        $out .= "<h2>" . $siteName . "</h2>\n";
        $user = $this->auth->currentUser();
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

    public function errorPage(int $status, string $message): string
    {
        http_response_code($status);
        $out = $this->header($message);
        $out .= "<h1>" . $this->text($message) . "</h1>\n";

        return $out . $this->footer();
    }
}
