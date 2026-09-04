<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Per-session CSRF token. One token per session, compared with hash_equals().
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();

        if (empty($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::KEY];
    }

    public static function verify(?string $token): bool
    {
        Session::start();

        return is_string($token)
            && $token !== ''
            && !empty($_SESSION[self::KEY])
            && is_string($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }
}
