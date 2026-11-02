<?php

declare(strict_types=1);

namespace Art4\LegacyTodo;

/**
 * Value object over one raw HTTP response from the built-in test server.
 */
final class E2eResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(private readonly int $status, private readonly array $headers, private readonly string $body) {}

    public function status(): int
    {
        return $this->status;
    }

    /** @return string|null */
    public function header(string $name)
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /** @return string|null */
    public function location()
    {
        return $this->header('Location');
    }

    public function body(): string
    {
        return $this->body;
    }

    public function contains(string $needle): bool
    {
        return str_contains($this->body, $needle);
    }
}
