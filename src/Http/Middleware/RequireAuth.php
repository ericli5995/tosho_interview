<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Security\Auth;

/** Guards admin routes: 401 JSON when there is no admin session. */
final class RequireAuth
{
    /** @param array<string,string> $params */
    public function handle(Request $request, array $params): ?Response
    {
        return Auth::check() ? null : Response::json(['error' => 'Unauthenticated'], 401);
    }
}
