<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Middleware\RequireAuth;
use App\Http\Middleware\VerifyCsrf;

/**
 * Bootstrap + HTTP lifecycle for the JSON API.
 *   boot()  config, error reporting, Db binding   (web + CLI)
 *   run()   session -> router -> dispatch -> send  (web)
 */
final class Application
{
    public function __construct(private string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        defined('BASE_PATH') || define('BASE_PATH', $this->basePath);
    }

    public function boot(): self
    {
        Config::loadEnv("{$this->basePath}/config/.env");
        Config::load("{$this->basePath}/config");

        error_reporting(E_ALL);
        ini_set('display_errors', Config::get('app.debug', false) ? '1' : '0');

        // Bound lazily so CLI tools can report a friendly error if MySQL is down.
        App::bind('db', static fn (): Db => Db::connect(Config::all('database')));

        return $this;
    }

    public function run(): void
    {
        $this->boot();
        App::db();
        $this->startSession();

        $router = new Router();
        $router->registerMiddleware('auth', [new RequireAuth(), 'handle']);
        $router->registerMiddleware('csrf', [new VerifyCsrf(), 'handle']);
        $router->loadRoutes(require "{$this->basePath}/config/routes.php");

        try {
            $response = $router->dispatch(Request::capture());
        } catch (\Throwable $e) {
            error_log((string) $e);
            $response = Response::json(
                ['error' => Config::get('app.debug', false) ? (string) $e : 'Server error'],
                500
            );
        }

        $response->send();
    }

    private function startSession(): void
    {
        ini_set('session.gc_maxlifetime', '1440');
        ini_set('session.use_strict_mode', '1');

        $path = "{$this->basePath}/storage/sessions";
        if (is_dir($path) && is_writable($path)) {
            session_save_path($path);
            // Custom save_path is outside the OS session-gc cron: run PHP's own
            // collector (~1% of requests). Production: gc_probability 0 + a scheduled sweep.
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
    }
}
