<?php

declare(strict_types=1);

/**
 * Front controller. Apache (or the PHP built-in server) routes every request
 * that is not a real file to this script.
 */

use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;
use App\Http\Middleware\RequireAuth;
use App\Http\Middleware\VerifyCsrf;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/Core/Autoloader.php';
(new App\Core\Autoloader('App\\', BASE_PATH . '/src'))->register();
require BASE_PATH . '/src/Support/helpers.php';

Config::loadEnv(BASE_PATH . '/config/.env');
Config::load(BASE_PATH . '/config');

error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.debug', false) ? '1' : '0');

/* --- Session ------------------------------------------------------------- */
$sessionPath = BASE_PATH . '/storage/sessions';
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
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

$GLOBALS['_old_input'] = is_array($_SESSION['_old'] ?? null) ? $_SESSION['_old'] : [];
unset($_SESSION['_old']);
$flash = flash_take();

/* --- Container --------------------------------------------------------- */
$db = Database::connect(Config::all('database'));
App::bind('db', $db);

$view = new View(BASE_PATH . '/templates', [
    'appName' => Config::get('app.name', 'THINK ENGINEERING'),
    'flash' => $flash,
]);
App::bind('view', $view);

/* --- Router ---------------------------------------------------------- */
$router = new Router($view);
$router->registerMiddleware('auth', [new RequireAuth(), 'handle']);
$router->registerMiddleware('csrf', [new VerifyCsrf(), 'handle']);
$router->loadRoutes(require BASE_PATH . '/config/routes.php');

/* --- Dispatch ------------------------------------------------------- */
$request = Request::capture();

try {
    $response = $router->dispatch($request);
} catch (\Throwable $e) {
    error_log((string) $e);

    if (Config::get('app.debug', false)) {
        $response = Response::html('<pre>' . e((string) $e) . '</pre>', 500);
    } else {
        $response = Response::html(
            $view->render('errors/500', ['title' => 'エラーが発生しました'], 'layouts/public'),
            500
        );
    }
}

$response->send();
