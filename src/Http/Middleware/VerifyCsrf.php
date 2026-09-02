<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Security\Csrf;

/**
 * Rejects state-changing requests whose `_token` field does not match the
 * session CSRF token.
 */
final class VerifyCsrf
{
    /** @param array<string,string> $params */
    public function handle(Request $request, array $params): ?Response
    {
        $token = $request->post('_token');

        if (Csrf::verify(is_string($token) ? $token : null)) {
            return null;
        }

        return Response::html('CSRF token mismatch (419). Please reload and try again.', 419);
    }
}
