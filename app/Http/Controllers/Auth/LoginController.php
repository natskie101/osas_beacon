<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use App\Services\LoginThrottle;
use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;
use App\Support\Session;

final class LoginController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->page('layouts.auth', 'auth/login', [
            'title' => 'Sign in to Portal',
            'pageTitle' => 'Sign in to Portal',
            'errors' => Session::errors(),
            'error' => Session::getFlash('error'),
            'status' => Session::getFlash('status'),
            'registrationSuccess' => Session::getFlash('registration_success'),
            'old' => ['email' => Session::old('email', '')],
            'demoMode' => (bool) config('app.demo_mode', false),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = (new LoginRequest())->validate($request);
        $ip = $request->ip;

        if (LoginThrottle::tooManyAttempts($data['email'], $ip)) {
            Session::flashErrors(['email' => 'Too many failed attempts. Please wait 10 minutes before trying again.']);
            Session::flashOld(['email' => $data['email']]);

            return Response::redirect(url('/login'));
        }

        $result = Auth::attempt($data['email'], $data['password'], $request->boolean('remember'));

        if (!$result['ok']) {
            LoginThrottle::recordFailure($data['email'], $ip);
            Session::flashErrors(['email' => $result['message']]);
            Session::flashOld(['email' => $data['email']]);

            return Response::redirect(url('/login'));
        }

        LoginThrottle::clear($data['email'], $ip);
        AuditLogger::login($result['user'], $ip, $request->header('user-agent'));

        $role = Auth::role();
        $intended = Session::pull('url.intended');
        $target = $this->safeIntended($intended, $role) ?? url($role?->homePath() ?? '/login');

        return Response::redirect($target);
    }

    private function safeIntended(mixed $intended, ?Role $role): ?string
    {
        if ($role === null || !is_string($intended) || $intended === '' || $intended === '/login') {
            return null;
        }

        if (!str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            return null;
        }

        $prefix = $role === Role::Student ? '/student/' : '/admin/';

        if (!str_starts_with($intended, $prefix)) {
            return null;
        }

        return url($intended);
    }
}
