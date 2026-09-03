<?php

declare(strict_types=1);

/**
 * Delete expired session files from storage/sessions.
 *
 * In production, disable PHP's probabilistic GC (session.gc_probability = 0) and
 * run this on a schedule instead. Example crontab line (every 15 minutes):
 *
 *   0,15,30,45 * * * *  php /path/to/bin/gc-sessions.php >> /path/to/storage/logs/gc.log 2>&1
 *
 * TTL defaults to 1440s (matches session.gc_maxlifetime); override with SESSION_TTL.
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$directory = dirname(__DIR__) . '/storage/sessions';

$ttlEnv = getenv('SESSION_TTL');
$ttl = ($ttlEnv === false || $ttlEnv === '') ? 1440 : max(0, (int) $ttlEnv);
$cutoff = time() - $ttl;

$removed = 0;
$kept = 0;
foreach (glob($directory . '/sess_*') ?: [] as $file) {
    if (!is_file($file)) {
        continue;
    }
    if (filemtime($file) < $cutoff) {
        if (@unlink($file)) {
            $removed++;
        }
    } else {
        $kept++;
    }
}

printf(
    "[%s] gc-sessions: removed %d expired, kept %d active (ttl %ds) in %s\n",
    date('Y-m-d H:i:s'),
    $removed,
    $kept,
    $ttl,
    $directory
);
