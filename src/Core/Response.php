<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A JSON HTTP response. Controllers return one; Application sends it.
 */
final class Response
{
    /** @param array<string,string> $headers */
    private function __construct(
        private string $body,
        private int $status,
        private array $headers = [],
    ) {
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self($json === false ? '{}' : $json, $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers + ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store'] as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }
}
