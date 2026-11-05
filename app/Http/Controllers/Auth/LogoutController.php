<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class LogoutController extends Controller
{
    public function destroy(Request $request): Response
    {
        $user = Auth::user();

        if ($user !== null) {
            AuditLogger::logout($user, $request->ip, $request->header('user-agent'));
        }

        Auth::logout();
        Session::flash('status', 'You have signed out safely.');

        return Response::redirect(url('/login'));
    }
}
