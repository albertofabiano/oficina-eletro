<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, string $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, string $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function delete(string $path, string $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, string $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method'      => $method,
            'path'        => $path,
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            $pattern = $this->buildPattern($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                // run middlewares
                foreach ($route['middlewares'] as $mw) {
                    $mwClass = "App\\Middleware\\{$mw}";
                    (new $mwClass())->handle();
                }
                // parse params
                array_shift($matches);
                $params = array_values(array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY));
                // dispatch handler
                [$controllerName, $action] = explode('@', $route['handler']);
                $controllerClass = "App\\Controllers\\{$controllerName}";
                $controller = new $controllerClass();
                $controller->$action(...$params);
                return;
            }
        }

        http_response_code(404);
        require BASE_PATH . '/app/Views/errors/404.php';
    }

    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
