<?php

declare(strict_types=1);

/**
 * Re-download the pinned front-end libraries and verify their SHA-256 hashes.
 * This is the entire "package manager" for JavaScript in this project - no npm.
 *
 *   php bin/vendor-sync.php
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$dest = dirname(__DIR__) . '/public/assets/js/vendor';

$libraries = [
    [
        'file' => 'vue.global.prod.js',
        'version' => '3.4.38',
        'url' => 'https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.38/vue.global.prod.js',
        'sha256' => 'b50eeefe35d41636bb96c92b40f1df0b4fb7914e07b3c625b1ec15e9748767b9',
    ],
    [
        'file' => 'jquery.min.js',
        'version' => '3.7.1',
        'url' => 'https://code.jquery.com/jquery-3.7.1.min.js',
        'sha256' => 'fc9a93dd241f6b045cbff0481cf4e1901becd0e12fb45166a8f17f95823f0b1a',
    ],
];

$failed = false;

foreach ($libraries as $lib) {
    echo "-> {$lib['file']} ({$lib['version']})\n";

    $body = @file_get_contents($lib['url']);
    if ($body === false) {
        fwrite(STDERR, "   download failed: {$lib['url']}\n");
        $failed = true;
        continue;
    }

    $got = hash('sha256', $body);
    if (!hash_equals($lib['sha256'], $got)) {
        fwrite(STDERR, "   SHA-256 MISMATCH\n   expected {$lib['sha256']}\n   got      {$got}\n");
        $failed = true;
        continue;
    }

    file_put_contents($dest . '/' . $lib['file'], $body);
    echo "   ok {$got}\n";
}

if ($failed) {
    fwrite(STDERR, "One or more libraries could not be verified.\n");
    exit(1);
}

echo "All vendor files verified.\n";
