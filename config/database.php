<?php

declare(strict_types=1);

/**
 * Database connection settings. Values come from environment variables (see docker-compose.yml).
 */

return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_NAME', 'tosho_dev'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
