<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Route table matcher. Routes are `[method, path, [Controller::class, 'action'], [middleware...]]`.
 * `{param}` placeholders are passed to the action as $params.
 */
final class Router
{
    /** @var list<array{method:string,pattern:string,handler:array{0:class-string,1:string},middleware:list<string>}> */
    private array $routes = [];

    /** @var array<string,callable> */
    private array $middleware = [];

    public function registerMiddleware(string $name, callable $handler): void
    {
        $this->middleware[$name] = $handler;
    }

    /** @param iterable<array{0:string,1:string,2:array,3?:list<string>}> $definitions */
    public function loadRoutes(iterable $definitions): void
    {
        foreach ($definitions as $definition) {
            $this->routes[] = [
                'method' => strtoupper($definition[0]),
                'pattern' => $this->compile($definition[1]),
                'handler' => $definition[2],
                'middleware' => $definition[3] ?? [],
            ];
        }
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $name) {
                $result = ($this->middleware[$name])($request, $params);
                if ($result instanceof Response) {
                    return $result;
                }
            }

            [$class, $action] = $route['handler'];

            return (new $class())->{$action}($request, $params);
        }

        return Response::json(['error' => $pathMatched ? 'Method not allowed' : 'Not found'], $pathMatched ? 405 : 404);
    }

    /** `/products/{slug}` -> `#^/products/(?P<slug>[^/]+)$#`, literals regex-escaped. */
    private function compile(string $path): string
    {
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}|([^{]+)/',
            static fn (array $m): string => isset($m[2]) ? preg_quote($m[2], '#') : '(?P<' . $m[1] . '>[^/]+)',
            $path
        );

        return '#^' . $pattern . '$#';
    }
}
