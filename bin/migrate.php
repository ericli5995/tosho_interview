<?php

declare(strict_types=1);

/**
 * Minimal migration runner.
 *
 *   php bin/migrate.php            apply pending migrations
 *   php bin/migrate.php --seed     apply, then load sql/seed.sql
 *   php bin/migrate.php --fresh    drop known tables first, then apply
 *   php bin/migrate.php --fresh --seed
 */

use App\Core\Config;
use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/Core/Autoloader.php';
(new App\Core\Autoloader('App\\', BASE_PATH . '/src'))->register();
require BASE_PATH . '/src/Support/helpers.php';

Config::loadEnv(BASE_PATH . '/config/.env');
Config::load(BASE_PATH . '/config');

$options = array_slice($argv, 1);
$seed = in_array('--seed', $options, true);
$fresh = in_array('--fresh', $options, true);

try {
    $pdo = Database::connect(Config::all('database'));
} catch (\Throwable $e) {
    fwrite(STDERR, "Cannot connect to the database: {$e->getMessage()}\n");
    fwrite(STDERR, "Check config/.env (DB_HOST, DB_NAME, DB_USER, DB_PASS) and that MySQL is running.\n");
    exit(1);
}

$tablesInReverse = ['product_images', 'product_specs', 'products', 'categories', 'admin_users', 'schema_migrations'];

if ($fresh) {
    echo "Dropping existing tables...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tablesInReverse as $table) {
        $pdo->exec("DROP TABLE IF EXISTS {$table}");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations ('
    . 'version VARCHAR(255) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL'
    . ') ENGINE = InnoDB DEFAULT CHARSET = utf8mb4'
);

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(BASE_PATH . '/sql/migrations/*.sql') ?: [];
sort($files);

$ran = 0;
foreach ($files as $file) {
    $version = basename($file, '.sql');
    if (in_array($version, $applied, true)) {
        continue;
    }

    echo "  applying {$version} ... ";
    $pdo->exec((string) file_get_contents($file));

    $stmt = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)');
    $stmt->execute([$version, date('Y-m-d H:i:s')]);

    echo "ok\n";
    $ran++;
}

echo $ran === 0 ? "No pending migrations.\n" : "Applied {$ran} migration(s).\n";

if ($seed) {
    $seedFile = BASE_PATH . '/sql/seed.sql';
    if (is_file($seedFile)) {
        echo "Seeding demo data ... ";
        $pdo->exec((string) file_get_contents($seedFile));
        echo "ok\n";
    } else {
        fwrite(STDERR, "seed file not found: {$seedFile}\n");
    }
}

echo "Done.\n";
