<?php

declare(strict_types=1);

use App\Core\Config;
use App\Security\Csrf;

/**
 * Global view/helper functions. Loaded once during bootstrap (before config).
 */

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        return Config::envValue($key) ?? $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('media_url')) {
    function media_url(?string $relPath): string
    {
        return '/media/' . ltrim((string) $relPath, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('str_slug')) {
    function str_slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('flash_take')) {
    /** @return list<array{type:string,message:string}> */
    function flash_take(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($flash) ? $flash : [];
    }
}

if (!function_exists('set_old')) {
    /** @param array<string,mixed> $data */
    function set_old(array $data): void
    {
        $_SESSION['_old'] = $data;
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $value = $GLOBALS['_old_input'][$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }
}

if (!function_exists('normalize_files')) {
    /**
     * Turn a $_FILES entry (single or `name="x[]"` multiple) into a flat list
     * of `['name','type','tmp_name','error','size']` rows.
     *
     * @param array<string,mixed>|null $files
     * @return list<array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    function normalize_files(?array $files): array
    {
        if ($files === null || !isset($files['name'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            return [[
                'name' => (string) $files['name'],
                'type' => (string) ($files['type'] ?? ''),
                'tmp_name' => (string) ($files['tmp_name'] ?? ''),
                'error' => (int) ($files['error'] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($files['size'] ?? 0),
            ]];
        }

        $out = [];
        foreach (array_keys($files['name']) as $i) {
            $out[] = [
                'name' => (string) ($files['name'][$i] ?? ''),
                'type' => (string) ($files['type'][$i] ?? ''),
                'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
                'error' => (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($files['size'][$i] ?? 0),
            ];
        }

        return $out;
    }
}
