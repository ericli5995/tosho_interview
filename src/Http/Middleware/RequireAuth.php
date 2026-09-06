<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Security\Auth;

/**
 * Guards admin routes: 401 JSON unless the session belongs to an admin that
 * still exists. A session whose admin was deleted is ended on the spot, so
 * deletion takes effect immediately rather than at session expiry.
 */
final class RequireAuth
{
    /** @param array<string,string> $params */
    public function handle(Request $request, array $params): ?Response
    {
        if (Auth::user() !== null) {
            return null;
        }
        if (Auth::check()) {
            Auth::logout();
        }

        return Response::json(['error' => 'Unauthenticated'], 401);
    }
}
