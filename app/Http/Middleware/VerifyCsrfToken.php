<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Csrf;
use App\Support\Exceptions\HttpException;
use App\Support\Request;
use App\Support\Response;
use Closure;
use Illuminate\Container\Container;

final class VerifyCsrfToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // When running under Laravel, Laravel's global VerifyCsrfToken middleware already validates the token
        if (class_exists(Container::class) && Container::getInstance()) {
            return $next($request);
        }

        if (!in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $token = $request->string('_token');

        if ($token === '') {
            $token = (string) $request->header('x-csrf-token');
        }

        if (!Csrf::validate($token)) {
            throw new HttpException(419, 'Your session expired. Please refresh the page and try again.');
        }

        return $next($request);
    }
}
