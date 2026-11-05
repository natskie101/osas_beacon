<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;
use Closure;

final class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = Auth::role();

        if ($role !== null) {
            return Response::redirect(url($role->homePath()));
        }

        return $next($request);
    }
}
