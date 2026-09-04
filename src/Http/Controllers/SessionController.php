<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Entity\AdminUser;
use App\Security\Auth;
use App\Security\Csrf;

/**
 * Admin session: the front end calls GET /api/session on every page load to
 * obtain the CSRF token and learn whether an admin is logged in.
 */
final class SessionController extends Controller
{
    /** GET /api/session */
    public function show(): Response
    {
        return $this->json(['csrf' => Csrf::token(), 'user' => self::payload(Auth::user())]);
    }

    /** POST /api/admin/login  {email, password} */
    public function login(Request $request): Response
    {
        $email = trim((string) $request->post('email', ''));
        $password = (string) $request->post('password', '');

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            return $this->error('メールアドレスまたはパスワードが正しくありません。', 401);
        }

        return $this->json(['csrf' => Csrf::token(), 'user' => self::payload(Auth::user())]);
    }

    /** POST /api/admin/logout */
    public function logout(): Response
    {
        Auth::logout();

        return Response::noContent();
    }

    /** @return array<string,mixed>|null */
    private static function payload(?AdminUser $user): ?array
    {
        return $user === null ? null : ['id' => $user->id, 'email' => $user->email, 'name' => $user->name];
    }
}
