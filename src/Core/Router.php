<?php

namespace PicShare\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = strtok($uri, '?');
        $uri = '/' . trim($uri, '/');

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $pattern => $handler) {
            $regex = preg_replace('/\{([a-z_]+)\}/', '([^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);

                preg_match_all('/\{([a-z_]+)\}/', $pattern, $paramNames);
                $params = array_combine($paramNames[1], $matches) ?: [];

                if (is_array($handler)) {
                    [$class, $method_name] = $handler;
                    $controller = new $class();
                    $controller->$method_name($params);
                } else {
                    $handler($params);
                }
                return;
            }
        }

        $this->notFound();
    }

    private function notFound(): void
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
    }
}
