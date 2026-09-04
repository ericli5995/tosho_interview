<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Lazy, hardened PHP session. Nothing starts a session until Auth or Csrf
 * actually needs one, so anonymous catalogue requests stay stateless and
 * create no session files.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.gc_maxlifetime', '1440');
        ini_set('session.use_strict_mode', '1');

        $path = BASE_PATH . '/storage/sessions';
        if (is_dir($path) && is_writable($path)) {
            session_save_path($path);
            // A custom save_path is outside the OS session-gc cron: run PHP's own
            // collector (~1% of requests). Production: gc_probability 0 + a scheduled sweep.
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (($_SERVER['HTTPS'] ?? '') === 'on'),
        ]);
        session_name('tosho_session');
        session_start();
    }
}
