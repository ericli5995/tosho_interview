<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller: convenience wrappers around View and Response.
 */
abstract class Controller
{
    /** @param array<string,mixed> $data */
    protected function view(string $template, array $data = [], string $layout = 'layouts/public'): Response
    {
        return Response::html(App::view()->render($template, $data, $layout));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    protected function notFound(): Response
    {
        return Response::html(
            App::view()->render('errors/404', ['title' => 'ページが見つかりません'], 'layouts/public'),
            404
        );
    }
}
