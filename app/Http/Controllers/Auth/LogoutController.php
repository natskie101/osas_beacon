<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
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

        Auth::logout();
        session()->flash('status', 'You have signed out safely.');

        return redirect(url('/login'));
    }
}
