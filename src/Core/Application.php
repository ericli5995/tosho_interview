<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Middleware\RequireAuth;
use App\Http\Middleware\VerifyCsrf;

/**
 * Bootstrap + HTTP lifecycle for the JSON API.
 *   boot()  config, error reporting, Db binding   (web + CLI)
 *   run()   router -> dispatch -> send             (web)
 * Sessions are started lazily by Security\Session when Auth/Csrf need them.
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
}
