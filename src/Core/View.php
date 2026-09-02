<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renders plain PHP templates from templates/ and wraps them in a layout.
 * Templates receive $view (this instance) plus any shared/passed data.
 */
final class View
{
    private string $basePath;

    /** @param array<string,mixed> $shared */
    public function __construct(string $basePath, private array $shared = [])
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function render(string $name, array $data = [], ?string $layout = 'layouts/public'): string
    {
        $content = $this->renderPartial($name, $data);

        if ($layout === null) {
            return $content;
        }

        return $this->renderPartial($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * @param array<string,mixed> $data
     */
    public function renderPartial(string $name, array $data = []): string
    {
        $file = $this->basePath . '/' . ltrim($name, '/') . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("Template not found: {$name}");
        }

        return self::renderFile($file, array_merge($this->shared, $data, ['view' => $this]));
    }

    /**
     * Isolated scope so templates cannot touch the View internals.
     *
     * @param array<string,mixed> $__vars
     */
    private static function renderFile(string $__file, array $__vars): string
    {
        extract($__vars, EXTR_SKIP);

        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
