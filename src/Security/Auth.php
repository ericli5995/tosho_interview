<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\AdminUserRepository;

/**
 * Session-based admin authentication. Stores only the user id in the session.
 */
final class Auth
{
    private const KEY = '_admin_user_id';

    public static function attempt(string $email, string $password): bool
    {
        $repo = new AdminUserRepository();
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
        session_regenerate_id(true);
        $_SESSION[self::KEY] = $userId;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::KEY]);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::KEY]);
    }

    public static function id(): ?int
    {
        $id = $_SESSION[self::KEY] ?? null;

        return $id === null ? null : (int) $id;
    }
}
