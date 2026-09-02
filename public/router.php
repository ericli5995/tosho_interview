<?php

declare(strict_types=1);

/**
 * Router script for the PHP built-in server (development only):
 *
 *   php -S localhost:8080 -t public public/router.php
 *
 * Real files under public/ (assets, and the media/ symlink to storage/uploads)
 * are served as-is; everything else is handed to the front controller.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
