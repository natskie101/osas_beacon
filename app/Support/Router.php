<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Exceptions\HttpException;
use App\Support\Exceptions\MethodNotAllowedHttpException;
use App\Support\Exceptions\NotFoundHttpException;
use Closure;

final class Router
{
    /** @var list<array{method: string, path: string, action: mixed, middleware: list<string|Closure>}> */
    private array $routes = [];

    /** @param list<string|Closure> $middleware */
    public function __construct(private array $middleware = [])
    {
    }

    /** @param array{class-string, string}|Closure $action */
    public function get(string $path, array|Closure $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    /** @param array{class-string, string}|Closure $action */
    public function post(string $path, array|Closure $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    /** @param array{class-string, string}|Closure $action */
    public function put(string $path, array|Closure $action, array $middleware = []): void
    {
        $this->add('PUT', $path, $action, $middleware);
    }

    /** @param array{class-string, string}|Closure $action */
    public function patch(string $path, array|Closure $action, array $middleware = []): void
    {
        $this->add('PATCH', $path, $action, $middleware);
    }

    /** @param array{class-string, string}|Closure $action */
    public function delete(string $path, array|Closure $action, array $middleware = []): void
    {
        $this->add('DELETE', $path, $action, $middleware);
    }

    /**
     * @param array{class-string, string}|Closure $action
     * @param list<string|Closure> $middleware
     */
    public function add(string $method, string $path, array|Closure $action, array $middleware = []): void
    {
        $normalized = '/' . trim($path, '/');

        if ($normalized === '//') {
            $normalized = '/';
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalized,
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];

        foreach ($this->routes as $route) {
            $pattern = $this->compile($route['path']);

            if (preg_match($pattern, $request->path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $request->method) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (mixed $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY,
            );

            $destination = function (Request $current) use ($route, $params): Response {
                return $this->runAction($route['action'], $current, $params);
            };

            $pipeline = $destination;

            foreach (array_reverse(array_merge($this->middleware, $route['middleware'])) as $middleware) {
                $next = $pipeline;
                $pipeline = fn (Request $current): Response => $this->runMiddleware($middleware, $current, $next);
            }

            return $pipeline($request);
        }

        if ($allowed !== []) {
            throw new MethodNotAllowedHttpException(array_values(array_unique($allowed)));
        }

        throw new NotFoundHttpException();
    }

    private function compile(string $path): string
    {
        if ($path === '/') {
            return '#^/$#';
        }

        $pattern = '';

        foreach (explode('/', trim($path, '/')) as $segment) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $match) === 1) {
                $pattern .= '/(?P<' . $match[1] . '>[^/]+)';
            } else {
                $pattern .= '/' . preg_quote($segment, '#');
            }
        }

        return '#^' . $pattern . '/?$#';
    }

    /**
     * @param array{class-string, string}|Closure $action
     * @param array<string, string> $params
     */
    private function runAction(array|Closure $action, Request $request, array $params): Response
    {
        if ($action instanceof Closure) {
            $result = $action($request, ...array_values($params));
        } else {
            [$class, $method] = $action;
            $controller = new $class();
            $result = $controller->{$method}($request, ...array_values($params));
        }

        if ($result instanceof Response) {
            return $result;
        }

        return Response::html((string) $result);
    }

    /** @param string|Closure $middleware */
    private function runMiddleware(string|Closure $middleware, Request $request, Closure $next): Response
    {
        if ($middleware instanceof Closure) {
            return $middleware($request, $next);
        }

        $name = $middleware;
        $params = [];

        if (str_contains($middleware, ':')) {
            [$name, $paramString] = explode(':', $middleware, 2);
            $params = array_values(array_filter(array_map('trim', explode(',', $paramString))));
        }

        $aliases = [
            'auth' => 'App\\Http\\Middleware\\Authenticate',
            'guest' => 'App\\Http\\Middleware\\RedirectIfAuthenticated',
            'role' => 'App\\Http\\Middleware\\EnsureRole',
            'csrf' => 'App\\Http\\Middleware\\VerifyCsrfToken',
        ];

        $class = $aliases[$name] ?? (str_contains($name, '\\')
            ? $name
            : 'App\\Http\\Middleware\\' . $name);

        if (!class_exists($class)) {
            throw new HttpException(500, 'Middleware not found: ' . $name);
        }

        $instance = new $class();

        return $instance->handle($request, $next, ...$params);
    }
}
