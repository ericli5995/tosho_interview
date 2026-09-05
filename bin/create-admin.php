<?php

declare(strict_types=1);

/**
 * Create an admin user, or reset the password of an existing one.
 *
 *   php bin/create-admin.php <email> <password> [name]
 *   docker compose exec app php bin/create-admin.php ops@example.com secret123
 */

use App\Core\App;
use App\Core\Application;
use App\Repository\AdminUserRepository;
use App\Security\Password;

require dirname(__DIR__) . '/vendor/autoload.php';
(new Application(dirname(__DIR__)))->boot();

[$email, $password, $name] = [$argv[1] ?? '', $argv[2] ?? '', $argv[3] ?? 'Administrator'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    exit("Usage: php bin/create-admin.php <email> <password (8+ chars)> [name]\n");
}

$admins = new AdminUserRepository(App::db());
if ($admins->findByEmail($email) !== null) {
    $admins->updatePassword($email, Password::hash($password));
    echo "Password updated for {$email}\n";
} else {
    $admins->create($email, Password::hash($password), $name);
    echo "Created admin {$email}\n";
}
