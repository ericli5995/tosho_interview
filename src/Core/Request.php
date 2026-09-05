<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable snapshot of the incoming HTTP request. Accepts form-encoded,
 * multipart and JSON bodies; all three end up in $body.
 */
final class Request
{
    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     * @param array<string,mixed> $files
     * @param array<string,mixed> $server
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $files,
        public readonly array $server,
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;
        $path = rawurldecode((string) (parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'));
        $path = $path === '/' ? '/' : (rtrim($path, '/') ?: '/');

        $body = $_POST;
        if ($body === [] && str_starts_with((string) ($server['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $body = is_array($decoded) ? $decoded : [];
        }

        return new self(strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET')), $path, $_GET, $body, $_FILES, $server);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * A single uploaded file's $_FILES row, or null when the field was empty
     * (or was posted as an array, which no endpoint accepts).
     *
     * @return array{name:string,type:string,tmp_name:string,error:int,size:int}|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || !is_string($file['tmp_name'] ?? null) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return isset($this->server[$key]) ? (string) $this->server[$key] : null;
    }
}
