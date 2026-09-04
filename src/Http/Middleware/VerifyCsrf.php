<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Security\Csrf;

/**
 * Rejects state-changing requests unless the X-CSRF-Token header (or a _token
 * body field, for plain forms) matches the session token. The front end gets
 * the token from GET /api/session.
 */
final class VerifyCsrf
{
    /** @param array<string,string> $params */
    public function handle(Request $request, array $params): ?Response
    {
        $token = $request->header('X-CSRF-Token') ?? $request->post('_token');

        return Csrf::verify(is_string($token) ? $token : null)
            ? null
            : Response::json(['error' => 'CSRF token mismatch'], 419);
    }
}
