<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Loads the `.env` file and the PHP config arrays under config/.
 * Values are read with dot notation, e.g. Config::get('app.debug').
 */
final class Config
{
    /** @var array<string,string> */
    private static array $env = [];

    /** @var array<string,mixed> */
    private static array $items = [];

    public static function loadEnv(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            $length = strlen($value);
            if ($length >= 2
                && (($value[0] === '"' && $value[$length - 1] === '"')
                    || ($value[0] === "'" && $value[$length - 1] === "'"))) {
                $value = substr($value, 1, -1);
            }

            self::$env[$key] = $value;
        }
    }

    public static function envValue(string $key): ?string
    {
        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }

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

    /** @param array<string,mixed> $data */
    public static function set(string $name, array $data): void
    {
        self::$items[$name] = $data;
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
