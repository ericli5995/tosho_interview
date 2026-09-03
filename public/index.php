<?php

declare(strict_types=1);

/**
 * Front controller - every non-file request is rewritten here (see .htaccess).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

(new App\Core\Application(dirname(__DIR__)))->run();
