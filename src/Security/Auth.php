<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\App;
use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;

/**
 * Session-based admin authentication. Stores only the user id in the session.
 */
final class Auth
{
    private const KEY = '_admin_user_id';

    public static function attempt(string $email, string $password): bool
    {
        $repo = new AdminUserRepository(App::db());
        $user = $repo->findByEmail($email);

        if ($user === null || !Password::verify($password, $user->passwordHash)) {
            return false;
        }

        if (Password::needsRehash($user->passwordHash)) {
            $repo->updatePassword($user->email, Password::hash($password));
        }

        self::login($user->id);
        $repo->touchLogin($user->id);

        return true;
    }

    public static function login(int $userId): void
    {
        Session::start();
        session_regenerate_id(true);
        $_SESSION[self::KEY] = $userId;
    }

    public static function logout(): void
    {
        Session::start();
        unset($_SESSION[self::KEY]);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        Session::start();
        $id = $_SESSION[self::KEY] ?? null;

        return $id === null ? null : (int) $id;
    }

    /** The logged-in admin, or null. */
    public static function user(): ?AdminUser
    {
        $id = self::id();

        return $id === null ? null : (new AdminUserRepository(App::db()))->find($id);
    }
}
