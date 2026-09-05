<?php

declare(strict_types=1);

/**
 * API front controller. Static pages (*.html, assets/) are served by Apache
 * directly; every other URL is rewritten here (docker/apache.conf) and
 * answered as JSON.
 *
 *   1. Autoload   vendor/autoload.php  (Composer, PSR-4: App\ -> src/, + helpers.php)
 *   2. Boot       Application::boot()  config, error reporting, Db
 *   3. Route      config/routes.php -> Router (middleware: auth, csrf)
 *   4. Dispatch   Router::dispatch() -> controller action -> Response (JSON)
 * Sessions start lazily (Security\Session) only when Auth or Csrf need one.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

(new App\Core\Application(dirname(__DIR__)))->run();
