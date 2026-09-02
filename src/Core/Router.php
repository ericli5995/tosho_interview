<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Route table matcher. Routes are `[method, path, [Controller::class, 'action'], [middleware...]]`.
 * Path placeholders look like `/products/{slug}` and are passed to the action as $params.
 */
final class Router
{
    /** @var list<array{method:string,pattern:string,handler:array{0:class-string,1:string},middleware:list<string>}> */
    private array $routes = [];

    /** @var array<string,callable> */
    private array $middleware = [];

    public function __construct(private View $view)
    {
    }

    public function registerMiddleware(string $name, callable $handler): void
    {
        $this->middleware[$name] = $handler;
    }

    /** @param iterable<array{0:string,1:string,2:array,3?:list<string>}> $definitions */
    public function loadRoutes(iterable $definitions): void
    {
        foreach ($definitions as $definition) {
            $this->routes[] = [
                'method' => strtoupper((string) $definition[0]),
                'pattern' => $this->compile((string) $definition[1]),
                'handler' => $definition[2],
                'middleware' => $definition[3] ?? [],
            ];
        }
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }

            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }

            foreach ($route['middleware'] as $name) {
                if (!isset($this->middleware[$name])) {
                    throw new \RuntimeException("Unknown middleware: {$name}");
                }

                $result = ($this->middleware[$name])($request, $params);
                if ($result instanceof Response) {
                    return $result;
                }
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $response = $controller->{$action}($request, $params);

            if (!$response instanceof Response) {
                throw new \RuntimeException("{$class}::{$action}() must return a " . Response::class . '.');
            }

            return $response;
        }

        $status = $pathMatched ? 405 : 404;

        return Response::html(
            $this->view->render('errors/404', ['title' => 'ページが見つかりません'], 'layouts/public'),
            $status
        );
    }

    private function compile(string $path): string
    {
        $parts = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $path, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $pattern = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $m) === 1) {
                $pattern .= '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $pattern .= preg_quote($part, '#');
            }
        }

        return '#^' . $pattern . '$#';
    }
}
