<?php

declare(strict_types=1);

/**
 * Front controller - the single entry point for every HTTP request.
 *
 * Apache rewrites any URL that is not a real file to this script (docker/apache.conf).
 *
 * What happens, and where:
 *
 *   1. Autoload   vendor/autoload.php (Composer, PSR-4: App\ -> src/)
 *                 Also loads src/Support/helpers.php (e(), csrf_field(), ...).
 *
 *   2. Boot       App\Core\Application::boot()
 *                 config/.env + config/*.php -> Config; error reporting;
 *                 binds the shared Db connection.
 *
 *   3. Session    Application::startSession()
 *                 storage/sessions, hardened cookie, one-shot "old input".
 *
 *   4. Route      Application::run() builds the Router from config/routes.php
 *                 and registers the middleware ('auth', 'csrf').
 *
 *   5. Dispatch   Router::dispatch() matches [method, path] -> runs middleware
 *                 -> instantiates the controller -> calls the action.
 *
 *   6. Respond    The controller returns a Response; Application::handle()
 *                 catches any throwable (500 page), then Response::send().
 *
 * Steps 2-6 live in src/Core/Application.php so this file stays declarative.
 */

require dirname(__DIR__) . '/vendor/autoload.php';                 // 1

(new App\Core\Application(dirname(__DIR__)))->run();               // 2-6
