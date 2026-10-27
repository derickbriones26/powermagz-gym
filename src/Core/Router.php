<?php

namespace App\Core;

class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, string $handler, array $mw = []): void
    {
        $this->routes['GET'][$path] = [$handler, $mw];
    }

    public function post(string $path, string $handler, array $mw = []): void
    {
        $this->routes['POST'][$path] = [$handler, $mw];
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Strip subfolder base path (e.g. /powermagz-gym)
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = rtrim($path, '/') ?: '/';

        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            require BASE_PATH . '/src/Views/errors/404.php';
            return;
        }

        [$handler, $mw] = $route;
        [$class, $action] = explode('@', $handler);
        $fqcn = "App\\Controllers\\{$class}";

        if (!class_exists($fqcn)) {
            http_response_code(500);
            exit("Controller {$fqcn} not found.");
        }

        foreach ($mw as $m) {
            if ($m === 'auth') require_login();
            elseif (str_starts_with($m, 'role:')) require_role(...explode(',', substr($m, 5)));
        }

        (new $fqcn())->$action();
    }
}
