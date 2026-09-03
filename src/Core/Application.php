<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Middleware\RequireAuth;
use App\Http\Middleware\VerifyCsrf;

/**
 * Application bootstrap. `boot()` loads config and wires the container (used by
 * both the web front controller and the CLI scripts); `run()` performs the full
 * HTTP lifecycle: session, routing, dispatch, error handling, send.
 */
final class Application
{
    public function __construct(private string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', $this->basePath);
        }
    }

    /**
     * Environment + config + container. The database connection is bound
     * lazily so CLI tools can report a friendly error if MySQL is unreachable.
     */
    public function boot(): self
    {
        Config::loadEnv($this->basePath . '/config/.env');
        Config::load($this->basePath . '/config');

        error_reporting(E_ALL);
        ini_set('display_errors', Config::get('app.debug', false) ? '1' : '0');

        App::bind('db', static fn (): \PDO => Database::connect(Config::all('database')));

        return $this;
    }

    public function run(): void
    {
        $this->boot();
        App::db(); // connect eagerly - a request without a database is an error

        $this->startSession();

        $view = new View($this->basePath . '/templates', [
            'appName' => Config::get('app.name', 'THINK ENGINEERING'),
            'flash' => flash_take(),
        ]);
        App::bind('view', $view);

        $router = new Router($view);
        $router->registerMiddleware('auth', [new RequireAuth(), 'handle']);
        $router->registerMiddleware('csrf', [new VerifyCsrf(), 'handle']);
        $router->loadRoutes(require $this->basePath . '/config/routes.php');

        $this->handle($router, $view, Request::capture())->send();
    }

    /* ------------------------------------------------------------------ */

    private function startSession(): void
    {
        ini_set('session.gc_maxlifetime', '1440');
        ini_set('session.use_strict_mode', '1');

        $path = $this->basePath . '/storage/sessions';
        if (is_dir($path) && is_writable($path)) {
            session_save_path($path);
            // A custom save_path is not covered by the OS session-gc cron, so run
            // PHP's probabilistic collector ourselves (~1% of requests). In
            // production prefer gc_probability 0 + a scheduled bin/gc-sessions.php.
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (($_SERVER['HTTPS'] ?? '') === 'on'),
        ]);
        session_name('tosho_session');
        session_start();

        // One-shot "old input" for form re-population after a validation redirect.
        $GLOBALS['_old_input'] = is_array($_SESSION['_old'] ?? null) ? $_SESSION['_old'] : [];
        unset($_SESSION['_old']);
    }

    private function handle(Router $router, View $view, Request $request): Response
    {
        try {
            return $router->dispatch($request);
        } catch (\Throwable $e) {
            error_log((string) $e);

            if (Config::get('app.debug', false)) {
                return Response::html('<pre>' . e((string) $e) . '</pre>', 500);
            }

            return Response::html(
                $view->render('errors/500', ['title' => 'エラーが発生しました'], 'layouts/public'),
                500
            );
        }
    }
}
