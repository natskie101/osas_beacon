<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Auth;
use App\Support\Session;
use Illuminate\Http\Request;

final class LogoutController extends Controller
{
    public function destroy(Request $request)
    {
        $user = Auth::user();

        if ($user !== null) {
            AuditLogger::logout($user, $request->ip(), $request->header('user-agent'));
        }

        Auth::logout();
        Session::flash('status', 'You have signed out safely.');

        return redirect(url('/login'));
    }
}
