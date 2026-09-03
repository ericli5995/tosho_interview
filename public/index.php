<?php

declare(strict_types=1);

/**
 * Front controller - every non-file request is rewritten here
 * (docker/apache.conf under Apache, public/router.php under `php -S`).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

(new App\Core\Application(dirname(__DIR__)))->run();
