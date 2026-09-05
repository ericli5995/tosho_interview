<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Global helper functions (autoloaded via composer "files").
 */

function env(string $key, ?string $default = null): ?string
{
    return Config::envValue($key) ?? $default;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** "TE-22BK" -> "te-22bk" */
function str_slug(string $value): string
{
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($value))), '-');
}
