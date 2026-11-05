<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Auth;
use App\Support\Exceptions\ForbiddenHttpException;
use App\Support\Request;
use App\Support\Response;
use Closure;

final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = Auth::role();

        if ($role === null) {
            return Response::redirect(url('/login'));
        }

        if (!in_array($role->value, $roles, true)) {
            throw new ForbiddenHttpException();
        }

        return $next($request);
    }
}
