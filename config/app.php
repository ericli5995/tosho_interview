<?php

declare(strict_types=1);

/**
 * Application settings. Values come from environment variables (see docker-compose.yml).
 */

return [
    'name' => env('APP_NAME', 'THINK ENGINEERING'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => env('APP_URL', 'http://localhost:8080'),

    'upload_max_bytes' => (int) env('UPLOAD_MAX_BYTES', (string) (5 * 1024 * 1024)),

    'paths' => [
        'uploads' => BASE_PATH . '/storage/uploads',
        'templates' => BASE_PATH . '/templates',
    ],
];
