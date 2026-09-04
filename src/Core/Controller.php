<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller: JSON response helpers. Every action returns a Response.
 */
abstract class Controller
{
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Error envelope: {"error": "...", "errors": {field: [messages]}}.
     *
     * @param array<string,list<string>> $errors
     */
    protected function error(string $message, int $status = 400, array $errors = []): Response
    {
        return Response::json(['error' => $message] + ($errors ? ['errors' => $errors] : []), $status);
    }

    protected function notFound(): Response
    {
        return $this->error('Not found', 404);
    }
}
