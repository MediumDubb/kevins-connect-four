<?php

namespace MediumDubb\ConnectFour;

class AppRouter
{
    protected array $routes = [];

    // Register a GET route
    public function get(string $path, array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    // Match request and call controller method
    public function resolve(string $url, string $method): void {
        // Strip query strings (e.g., /about?id=5 becomes /about)
        $path = parse_url($url, PHP_URL_PATH);

        if (isset($this->routes[$method][$path])) {
            [$controllerClass, $action] = $this->routes[$method][$path];

            if (class_exists($controllerClass) && method_exists($controllerClass, $action)) {
                $controller = new $controllerClass();
                $controller->$action();
                return;
            }
        }

        // Simple 404 handler
        http_response_code(404);
        echo "404 - Page Not Found";
    }
}