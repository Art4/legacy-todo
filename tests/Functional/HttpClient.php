<?php

declare(strict_types=1);

namespace Art4\LegacyTodo;

/**
 * curl wrapper around the built-in test server. One cookie jar per instance
 * (per test method), redirects asserted but not traversed unless asked.
 */
final class HttpClient
{
    private const MAX_REDIRECTS = 10;

    /** @var string */
    private $baseUrl;

    /** @var string */
    private $cookieJar;

    public function __construct(string $baseUrl, string $cookieJar)
    {
        $this->baseUrl = $baseUrl;
        $this->cookieJar = $cookieJar;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     */
    public function request(string $method, string $path, array $data = [], array $files = []): E2eResponse
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildPostFields($data, $files));
        } elseif (strcasecmp($method, 'GET') !== 0) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data !== []) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildPostFields($data, $files));
            }
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException('cURL request failed for ' . $path . ': ' . $error);
        }
        curl_close($ch);

        return $this->parse($raw);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     */
    public function requestFollowingRedirects(string $method, string $path, array $data = [], array $files = []): E2eResponse
    {
        $response = $this->request($method, $path, $data, $files);
        $hops = 0;
        while ($response->location() !== null && $hops < self::MAX_REDIRECTS) {
            $response = $this->request('GET', $response->location());
            $hops++;
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     * @return array<int|string, mixed>|string
     */
    private function buildPostFields(array $data, array $files)
    {
        if ($files === []) {
            return http_build_query($data);
        }
        $fields = $data;
        foreach ($files as $name => $file) {
            $fields[$name] = new \CURLFile(
                (string) $file['tmp_name'],
                (string) ($file['type'] ?? ''),
                (string) ($file['name'] ?? basename((string) $file['tmp_name'])),
            );
        }

        return $fields;
    }

    private function parse(string $raw): E2eResponse
    {
        $parts = explode("\r\n\r\n", $raw, 2);
        $headerLines = explode("\r\n", $parts[0]);
        $statusLine = array_shift($headerLines);
        preg_match('#^HTTP/\S+\s+(\d+)#', $statusLine, $statusMatch);
        $status = isset($statusMatch[1]) ? (int) $statusMatch[1] : 0;
        $headers = [];
        foreach ($headerLines as $line) {
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $headers[trim(substr($line, 0, $colon))] = trim(substr($line, $colon + 1));
        }

        return new E2eResponse($status, $headers, $parts[1] ?? '');
    }
}
