<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns the CSRF POST-protection contract behind
 * the page seam (issue #209, ADR-0007) — wraps Auth's session-bound token
 * into a hidden form field and the request-time guard.
 */
class Csrf
{
    public function __construct(private Auth $auth, private Layout $layout) {}

    public function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . $this->layout->attr($this->auth->csrfToken()) . '">';
    }

    /** @param array<string, mixed> $post */
    public function guard(array $post): void
    {
        if ($post !== [] && !$this->auth->validateCsrfToken($post["_csrf_token"] ?? null)) {
            echo $this->layout->errorPage(403, "CSRF token invalid");
            exit;
        }
    }
}
