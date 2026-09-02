<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable snapshot of the incoming HTTP request built from the superglobals.
 */
final class Request
{
    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     * @param array<string,mixed> $files
     * @param array<string,mixed> $server
     * @param array<string,mixed> $cookies
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $files,
        public readonly array $server,
        public readonly array $cookies,
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));

        $rawPath = parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = rawurldecode(is_string($rawPath) && $rawPath !== '' ? $rawPath : '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
            if ($path === '') {
                $path = '/';
            }
        }

        return new self($method, $path, $_GET, $_POST, $_FILES, $server, $_COOKIE);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string,mixed>|null */
    public function file(string $key): ?array
    {
        $value = $this->files[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function wantsJson(): bool
    {
        $accept = (string) ($this->server['HTTP_ACCEPT'] ?? '');

        return str_contains($accept, 'application/json') || str_ends_with($this->path, '.json');
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '');
    }
}
