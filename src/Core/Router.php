<?php

declare(strict_types=1);

namespace App\Core;

use App\Exception\NotFoundException;

final class Router
{
    /** @var array<int, array{method:string, regex:string, params:string[], handler:array, middleware:array}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    public function any(string $pattern, array $handler, array $middleware = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $pattern, $handler, $middleware);
        }
    }

    private function addRoute(string $method, string $pattern, array $handler, array $middleware): void
    {
        [$regex, $params] = $this->compile($pattern);

        $this->routes[] = [
            'method' => $method,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /** @return array{0:string,1:string[]} */
    private function compile(string $pattern): array
    {
        $params = [];
        $regex = preg_replace_callback('#\{(\w+)\}#', function (array $m) use (&$params): string {
            $params[] = $m[1];

            return $m[1] === 'id' ? '(\d+)' : '([^/]+)';
        }, $pattern);

        return ['#^' . $regex . '$#u', $params];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = rtrim($request->path(), '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['params'], $matches);

            foreach ($route['middleware'] as $middleware) {
                $callable = is_string($middleware) ? new $middleware() : $middleware;
                $result = $callable($request);
                if ($result instanceof Response) {
                    return $result;
                }
            }

            [$controllerClass, $methodName] = $route['handler'];
            $controller = new $controllerClass();

            return $controller->$methodName($request, ...array_values($params));
        }

        throw new NotFoundException('Маршрут не найден');
    }
}
