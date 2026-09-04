<?php

declare(strict_types=1);

namespace App\Core;


/**
 * Tiny service locator. Not a full DI container - it just holds the shared
 * Db connection and View so controllers (constructed with `new` by the Router)
 * can reach them without global variables.
 */
final class App
{
    /** @var array<string,mixed> */
    private static array $bindings = [];

    public static function bind(string $key, mixed $value): void
    {
        self::$bindings[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$bindings);
    }

    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, self::$bindings)) {
            throw new \RuntimeException("Service not bound: {$key}");
        }

        $value = self::$bindings[$key];

        return $value instanceof \Closure ? $value() : $value;
    }

    public static function db(): Db
    {
        return self::get('db');
    }

    public static function view(): View
    {
        return self::get('view');
    }

    public static function reset(): void
    {
        self::$bindings = [];
    }
}
