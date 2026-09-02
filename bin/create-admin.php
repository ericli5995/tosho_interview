<?php

declare(strict_types=1);

/**
 * Create (or reset the password of) an admin user.
 *
 *   php bin/create-admin.php <email> <password> [name]
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/Core/Autoloader.php';
(new App\Core\Autoloader('App\\', BASE_PATH . '/src'))->register();
require BASE_PATH . '/src/Support/helpers.php';

use App\Core\Config;
use App\Core\Database;
use App\Repository\AdminUserRepository;
use App\Security\Password;

Config::loadEnv(BASE_PATH . '/config/.env');
Config::load(BASE_PATH . '/config');

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
    Database::connect(Config::all('database'));
} catch (\Throwable $e) {
    fwrite(STDERR, "Cannot connect to the database: {$e->getMessage()}\n");
    exit(1);
}

$repo = new AdminUserRepository();
$hash = Password::hash($password);

if ($repo->findByEmail($email) !== null) {
    $repo->updatePassword($email, $hash);
    echo "Password updated for {$email}\n";
} else {
    $id = $repo->create($email, $hash, $name);
    echo "Created admin #{$id}: {$email}\n";
}
