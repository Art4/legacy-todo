<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the CSRF POST-protection contract behind
 * the page seam (issue #209, ADR-0007) — wraps Auth's session-bound token
 * into a hidden form field and the request-time guard.
 */
class Csrf
{
    /** @var Auth */
    private $auth;

    /** @var Layout */
    private $layout;

    public function __construct(Auth $auth, Layout $layout)
    {
        $this->auth = $auth;
        $this->layout = $layout;
    }

    public function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . $this->layout->attr($this->auth->csrfToken()) . '">';
    }

    /** @param array<string, mixed> $post */
    public function guard(array $post): void
    {
        if (!empty($post) && !$this->auth->validateCsrfToken($post["_csrf_token"] ?? null)) {
            http_response_code(403);
            echo "CSRF token invalid";
            exit;
        }
    }
}
