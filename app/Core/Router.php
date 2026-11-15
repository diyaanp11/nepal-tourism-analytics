<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable|array}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function any(string $pattern, callable|array $handler): void
    {
        $this->add('ANY', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $this->compile($pattern),
            'handler' => $handler,
        ];
    }

    private function compile(string $pattern): string
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern === '//') {
            $pattern = '/';
        }
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static fn(array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );
        return '#^' . $pattern . '$#';
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }
        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func_array($route['handler'], array_values($params));
                return;
            }
        }
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "404 Not Found\n";
    }
}