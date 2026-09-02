<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/Core/Autoloader.php';
(new App\Core\Autoloader('App\\', BASE_PATH . '/src'))->register();
require BASE_PATH . '/src/Support/helpers.php';
