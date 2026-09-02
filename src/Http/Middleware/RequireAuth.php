<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Security\Auth;

/**
 * Guards /admin/* routes. Returns a redirect Response (which the Router treats
 * as a short-circuit) when the visitor is not authenticated.
 */
final class RequireAuth
{
    /** @param array<string,string> $params */
    public function handle(Request $request, array $params): ?Response
    {
        if (Auth::check()) {
            return null;
        }

        flash_set('error', 'ログインが必要です。');

        return Response::redirect('/admin/login');
    }
}
