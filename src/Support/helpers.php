<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Global helper functions (autoloaded via composer "files").
 */

function env(string $key, ?string $default = null): ?string
{
    return Config::envValue($key) ?? $default;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** "TE-22BK" -> "te-22bk" */
function str_slug(string $value): string
{
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($value))), '-');
}

/**
 * Flatten a $_FILES entry (single or `name="x[]"`) into a list of file rows.
 *
 * @param array<string,mixed>|null $files
 * @return list<array{name:string,type:string,tmp_name:string,error:int,size:int}>
 */
function normalize_files(?array $files): array
{
    if ($files === null || !isset($files['name'])) {
        return [];
    }

    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $out = [];
    foreach (array_keys($names) as $i) {
        $pick = static fn (string $k, mixed $default) => is_array($files[$k] ?? null) ? ($files[$k][$i] ?? $default) : ($files[$k] ?? $default);
        $out[] = [
            'name' => (string) $pick('name', ''),
            'type' => (string) $pick('type', ''),
            'tmp_name' => (string) $pick('tmp_name', ''),
            'error' => (int) $pick('error', UPLOAD_ERR_NO_FILE),
            'size' => (int) $pick('size', 0),
        ];
    }

    return $out;
}
