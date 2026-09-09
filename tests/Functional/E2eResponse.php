<?php

declare(strict_types=1);

/**
 * Value object over one raw HTTP response from the built-in test server.
 */
final class E2eResponse
{
    /** @var int */
    private $status;

    /** @var array<string, string> */
    private $headers;

    /** @var string */
    private $body;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $status, array $headers, string $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

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
        return strpos($this->body, $needle) !== false;
    }
}