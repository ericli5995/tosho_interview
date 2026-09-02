<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal PSR-4 autoloader. Replaces Composer's autoload for a single "App\" root.
 */
final class Autoloader
{
    private string $prefix;
    private string $baseDir;

    public function __construct(string $prefix, string $baseDir)
    {
        $this->prefix = rtrim($prefix, '\\') . '\\';
        $this->baseDir = rtrim($baseDir, '/') . '/';
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    public function load(string $class): void
    {
        if (!str_starts_with($class, $this->prefix)) {
            return;
        }

        $relative = substr($class, strlen($this->prefix));
        $path = $this->baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require $path;
        }
    }
}
