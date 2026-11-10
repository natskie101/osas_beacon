<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Exceptions\HttpException;
use App\Support\Exceptions\MethodNotAllowedHttpException;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Route as LaravelRoute;
use Throwable;

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

        $route = [
            'method' => strtoupper($method),
            'path' => $normalized,
            'action' => $action,
            'middleware' => $middleware,
        ];

        $this->routes[] = $route;

        // Automatically register with Laravel Route facade if running inside Laravel
        if (class_exists(Container::class) && Container::getInstance()) {
            $laravelPath = ltrim($normalized, '/');
            if ($laravelPath === '') {
                $laravelPath = '/';
            }

            // Laravel dispatches route arguments positionally: it runs
            // array_values() on the parameters before invoking this callable
            // (see CallableDispatcher@dispatch), so the argument keys lose
            // their names. Remember the parameter names declared in the path
            // so the positional arguments can be mapped back to names — the
            // container resolves controller dependencies by name, and a name
            // mismatch crashes every {param} route with a
            // BindingResolutionException.
            $paramNames = [];

            foreach (explode('/', trim($normalized, '/')) as $segment) {
                if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $segmentMatch) === 1) {
                    $paramNames[] = $segmentMatch[1];
                }
            }

            LaravelRoute::match([strtoupper($method)], $laravelPath, function (mixed ...$params) use ($route, $paramNames) {
                Session::start();
                $portalRequest = Request::capture();

                $values = array_values($params);
                $namedParams = [];

                // Map positional arguments back to the route's parameter names.
                // When array_values() normalises the arguments, order in
                // $values matches the order the names appear in the path.
                foreach ($paramNames as $index => $name) {
                    if (array_key_exists($index, $values) && !is_object($values[$index])) {
                        $namedParams[$name] = (string) $values[$index];
                    }
                }

                // Preserve explicitly named keys if the caller ever provides them.
                foreach ($params as $key => $value) {
                    if (is_string($key) && !is_object($value)) {
                        $namedParams[$key] = (string) $value;
                    }
                }

                return $this->dispatchRoute($route, $portalRequest, $namedParams);
            });
        }
    }

    /**
     * @param array{method: string, path: string, action: mixed, middleware: list<string|Closure>} $route
     * @param array<string, string> $params
     */
    public function dispatchRoute(array $route, Request $request, array $params = []): Response
    {
        try {
            $destination = function (Request $current) use ($route, $params): Response {
                return $this->runAction($route['action'], $current, $params);
            };

            $pipeline = $destination;

            foreach (array_reverse(array_merge($this->middleware, $route['middleware'])) as $middleware) {
                $next = $pipeline;
                $pipeline = fn (Request $current): Response => $this->runMiddleware($middleware, $current, $next);
            }

            return $pipeline($request);
        } catch (ValidationException $exception) {
            Session::flashErrors($exception->errors());
            Session::flashOld($exception->old());

            return Response::redirect($request->previousUrl());
        } catch (HttpException $exception) {
            return Response::html(
                View::render('errors/error', [
                    'status' => $exception->statusCode,
                    'message' => $exception->getMessage() ?: 'The request could not be completed.',
                    'debug' => (bool) config('app.debug', false),
                    'exception' => $exception,
                ]),
                $exception->statusCode,
                $exception->headers,
            );
        } catch (Throwable $exception) {
            error_log((string) $exception);

            return Response::html(
                View::render('errors/error', [
                    'status' => 500,
                    'message' => 'An unexpected server error occurred. The details have been written to the application log.',
                    'debug' => (bool) config('app.debug', false),
                    'exception' => $exception,
                ]),
                500,
            );
        }
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

            return $this->dispatchRoute($route, $request, $params);
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
        if (class_exists(Container::class) && Container::getInstance()) {
            $container = Container::getInstance();
            if ($action instanceof Closure) {
                $result = $container->call($action, array_merge(['request' => $request], $params));
            } else {
                [$class, $method] = $action;
                $controller = $container->make($class);

                // Auto-detect whether controller action expects App\Support\Request or Illuminate\Http\Request
                $actionArgs = $params;
                $reflection = new \ReflectionMethod($class, $method);
                foreach ($reflection->getParameters() as $param) {
                    $type = $param->getType();
                    if ($type instanceof \ReflectionNamedType) {
                        $typeName = $type->getName();
                        if ($typeName === Request::class) {
                            $actionArgs[$param->getName()] = $request;
                        } elseif ($typeName === LaravelRequest::class) {
                            $actionArgs[$param->getName()] = request();
                        }
                    }
                }

                $result = $container->call([$controller, $method], $actionArgs);
            }
        } else {
            if ($action instanceof Closure) {
                $result = $action($request, ...array_values($params));
            } else {
                [$class, $method] = $action;
                $controller = new $class();
                $result = $controller->{$method}($request, ...array_values($params));
            }
        }

        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            $headers = [];
            foreach ($result->headers->all() as $name => $values) {
                $headers[$name] = implode(', ', $values);
            }

            return new Response((string) $result->getContent(), $result->getStatusCode(), $headers);
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
