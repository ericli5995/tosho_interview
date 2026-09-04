<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny service locator holding the per-request singletons (currently the Db).
 * Controllers are constructed with `new` by the Router, so they fetch their
 * collaborators from here and pass them down by constructor injection.
 */
final class App
{
    /** @var array<string,mixed> */
    private static array $bindings = [];

    public static function bind(string $key, mixed $value): void
    {
        self::$bindings[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, self::$bindings)) {
            throw new \RuntimeException("Service not bound: {$key}");
        }

        $value = self::$bindings[$key];
        if ($value instanceof \Closure) {
            $value = self::$bindings[$key] = $value();
        }

        return $value;
    }

    public static function db(): Db
    {
        return self::get('db');
    }
}
