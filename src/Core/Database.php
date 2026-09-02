<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * PDO connection factory. Keeps a single shared connection for the request.
 */
final class Database
{
    private static ?PDO $pdo = null;

    /** @param array<string,mixed> $config */
    public static function connect(array $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = (string) ($config['driver'] ?? 'mysql');

        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . ($config['database'] ?? ':memory:');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? '3306',
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            );
        }

        $pdo = new PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        if ($driver === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        self::$pdo = $pdo;

        return $pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            throw new \RuntimeException('Database connection has not been established.');
        }

        return self::$pdo;
    }

    public static function setConnection(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
