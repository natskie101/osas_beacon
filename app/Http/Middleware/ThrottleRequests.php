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
        $key = $request->ip() . '|' . $request->path();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            $retryAfter = RateLimiter::availableIn($key);
            throw new TooManyRequestsHttpException(retryAfter: $retryAfter);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        return $response;
    }
}
