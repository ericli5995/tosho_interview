<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A value object describing the response. Controllers return one; index.php sends it.
 */
final class Response
{
    /** @param array<string,string> $headers */
    private function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = [],
    ) {
    }

    public static function make(string $body = '', int $status = 200): self
    {
        return new self($body, $status);
    }

    public static function html(string $html, int $status = 200): self
    {
        return (new self($html, $status))->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (new self($json === false ? '{}' : $json, $status))
            ->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return (new self('', $status))->withHeader('Location', $location);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }

        echo $this->body;
    }
}
