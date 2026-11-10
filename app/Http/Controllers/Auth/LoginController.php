<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\ArrayUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LoginThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LoginController extends Controller
{
    public function show(Request $request)
    {
        return view('auth/login', [
            'title' => 'Sign in to Portal',
            'pageTitle' => 'Sign in to Portal',
            'errors' => session('errors', []),
            'error' => session('error'),
            'status' => session('status'),
            'registrationSuccess' => session('registration_success'),
            'old' => ['email' => old('email', '')],
            'demoMode' => (bool) config('app.demo_mode', false),
        ]);
    }

    public function store(Request $request)
    {
        $data = (new LoginRequest())->validate($request);
        $ip = $request->ip();

        if (LoginThrottle::tooManyAttempts($data['email'], $ip)) {
            session()->flash('errors', ['email' => 'Too many failed attempts. Please wait 10 minutes before trying again.']);
            session()->flashInput(['email' => $data['email']]);

            return redirect(url('/login'));
        }

        $result = $this->attempt($data['email'], $data['password'], $request->boolean('remember'));

        if (!$result['ok']) {
            LoginThrottle::recordFailure($data['email'], $ip);
            session()->flash('errors', ['email' => $result['message']]);
            session()->flashInput(['email' => $data['email']]);

            return redirect(url('/login'));
        }

        LoginThrottle::clear($data['email'], $ip);
        AuditLogger::login($result['user'], $ip, $request->header('user-agent'));

        $role = auth_role();
        $intended = session()->pull('url.intended');
        $target = $this->safeIntended($intended, $role) ?? url($role?->homePath() ?? '/login');

        return redirect($target);
    }

    /**
     * Credential check with the portal's legacy status gate. Mirrors the
     * retired App\Support\Auth::attempt() one-to-one, including the exact
     * messages each blocked status produces (all suite-tested).
     *
     * @return array{ok: bool, message: string, user?: array<string, mixed>}
     */
    private function attempt(string $email, string $password, bool $remember): array
    {
        $user = User::findByEmail($email);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return [
                'ok' => false,
                'message' => 'Incorrect email or password. Please try again.',
            ];
        }

        if ($user['status'] === 'Inactive') {
            return [
                'ok' => false,
                'message' => 'This account has been deactivated. Please contact OSAS Support.',
            ];
        }

        if ($user['status'] === 'Archived') {
            return [
                'ok' => false,
                'message' => 'This account has been archived. Please contact OSAS Support.',
            ];
        }

        if ($user['status'] === 'Pending') {
            return [
                'ok' => false,
                'message' => 'This account is still awaiting OSAS verification.',
            ];
        }

        User::recordLogin((int) $user['id']);
        $user['last_login_at'] = date('Y-m-d H:i:s');
        Auth::login(new ArrayUser($user), $remember);

        return ['ok' => true, 'message' => 'Signed in.', 'user' => $user];
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
