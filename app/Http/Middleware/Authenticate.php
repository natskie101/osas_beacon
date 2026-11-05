<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Auth;
use App\Support\Session;
use App\Support\Request;
use App\Support\Response;
use Closure;

final class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest()) {
            Session::put('url.intended', $request->path);
            Session::flash('error', 'Please sign in to continue.');
            Session::flash('old', ['email' => '']);

            return Response::redirect(url('/login'));
        }

        return $next($request);
    }
}
