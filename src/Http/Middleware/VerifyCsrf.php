<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Security\Csrf;

/**
 * Rejects state-changing requests unless the X-CSRF-Token header matches the
 * session token. The front end gets the token from GET /api/session.
 * Responds 403 (a standard code - Apache rewrites non-standard ones such as
 * 419 to 500).
 */
final class VerifyCsrf
{
    /** @param array<string,string> $params */
    public function handle(Request $request, array $params): ?Response
    {
        return Csrf::verify($request->header('X-CSRF-Token'))
            ? null
            : Response::json(['error' => 'CSRF token mismatch'], 403);
    }
}
