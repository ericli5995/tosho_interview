<?php

declare(strict_types=1);

/**
 * Create (or reset the password of) an admin user.
 *
 *   php bin/create-admin.php <email> <password> [name]
 */

use App\Core\App;
use App\Core\Application;
use App\Repository\AdminUserRepository;
use App\Security\Password;

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require dirname(__DIR__) . '/vendor/autoload.php';
(new Application(dirname(__DIR__)))->boot();

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name = $argv[3] ?? 'Administrator';

if ($email === null || $password === null) {
    fwrite(STDERR, "Usage: php bin/create-admin.php <email> <password> [name]\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email address.\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

try {
    App::db();
} catch (\Throwable $e) {
    fwrite(STDERR, "Cannot connect to the database: {$e->getMessage()}\n");
    exit(1);
}

$repo = new AdminUserRepository(App::db());
$hash = Password::hash($password);

if ($repo->findByEmail($email) !== null) {
    $repo->updatePassword($email, $hash);
    echo "Password updated for {$email}\n";
} else {
    $id = $repo->create($email, $hash, $name);
    echo "Created admin #{$id}: {$email}\n";
}
