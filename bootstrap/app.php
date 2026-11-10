<?php

declare(strict_types=1);

use App\Support\Exceptions\HttpException;
use App\Support\Exceptions\ValidationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'throttle' => \App\Http\Middleware\ThrottleRequests::class,
        ]);

        $middleware->validateCsrfTokens();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The portal's legacy exceptions predate the native kernel (they were
        // caught by App\Support\Router). Reproduce that behaviour here so
        // validation failures flash errors + old input and redirect back
        // instead of bubbling up as 500s.
        $exceptions->render(function (ValidationException $e) {
            \App\Support\Session::flashErrors($e->errors());
            \App\Support\Session::flashOld($e->old());

            return redirect()->back();
        });

        // Portal HTTP errors (404/403/405/429) render the branded error page
        // with the exception's real status code, as the old Router did.
        $exceptions->render(function (HttpException $e) {
            $response = response()->view('errors/error', [
                'status' => $e->statusCode,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'The request could not be completed.',
                'debug' => (bool) config('app.debug', false),
                'exception' => $e,
            ], $e->statusCode);

            foreach ($e->headers as $name => $value) {
                $response->headers->set($name, $value);
            }

            return $response;
        });
    })->create();
