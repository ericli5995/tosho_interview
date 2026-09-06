<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Loads the PHP config arrays under config/ (which read environment variables
 * via env()). Values are read with dot notation, e.g. Config::get('app.debug').
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    public static function envValue(string $key): ?string
    {
        $value = getenv($key);

        return $value === false ? null : $value;
    }

    public static function load(string $configDir): void
    {
        foreach (['app', 'database'] as $name) {
            $file = $configDir . '/' . $name . '.php';
            if (is_file($file)) {
                self::$items[$name] = require $file;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /** @return array<string,mixed> */
    public static function all(string $name): array
    {
        $value = self::$items[$name] ?? [];

        return is_array($value) ? $value : [];
    }
}
