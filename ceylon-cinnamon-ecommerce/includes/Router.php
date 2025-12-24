<?php
/**
 * Simple Router Class
 * Handles URL routing to controllers and actions
 */

declare(strict_types=1);

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): self
    {
        $pattern = $this->convertToRegex($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'controller' => $handler[0],
            'action' => $handler[1],
            'middleware' => $middleware
        ];
        return $this;
    }

    private function convertToRegex(string $path): string
    {
        // Convert route parameters like {id} to regex groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(string $uri, string $method): void
    {
        // Remove query string from URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    if (!$middlewareInstance->handle()) {
                        return;
                    }
                }

                // Instantiate controller and call action
                $controller = new $route['controller']();
                $action = $route['action'];

                if (!method_exists($controller, $action)) {
                    $this->sendError(500, "Action '{$action}' not found in controller.");
                    return;
                }

                call_user_func_array([$controller, $action], $params);
                return;
            }
        }

        // No route matched
        $this->sendError(404, 'Page not found');
    }

    private function sendError(int $code, string $message): void
    {
        http_response_code($code);
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message, 'code' => $code]);
        } else {
            // Load error view if exists
            $errorView = VIEWS_PATH . "/errors/{$code}.php";
            if (file_exists($errorView)) {
                include $errorView;
            } else {
                echo "<h1>Error {$code}</h1><p>{$message}</p>";
            }
        }
    }

    private function isApiRequest(): bool
    {
        return str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }
}
