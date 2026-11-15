<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\RateLimiter;
use App\Support\Exceptions\TooManyRequestsHttpException;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decaySeconds = 60): Response
    {
        // Method is part of the key so a GET page view never consumes the
        // budget of the POST route that shares the same path.
        $key = $request->ip() . '|' . $request->method() . '|' . $request->path();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            $retryAfter = RateLimiter::availableIn($key);
            throw new TooManyRequestsHttpException(retryAfter: $retryAfter);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        return $response;
    }
}
