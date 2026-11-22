<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\LastEmailCookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

final class LogoutController extends Controller
{
    public function destroy(Request $request)
    {
        $user = auth_user();

        if ($user !== null) {
            AuditLogger::logout($user, $request->ip(), $request->header('user-agent'));
        }

        $email = (string) ($user['email'] ?? '');

        Auth::logout();

        // After Auth::logout(): the guard flushes the session, so anything
        // flashed before it would be wiped. The cookie itself is independent
        // of the session. The password is never remembered - always blank.
        LastEmailCookie::set($email);

        session()->flash('status', 'You have signed out safely.');

        return redirect(url('/login'));
    }
}
