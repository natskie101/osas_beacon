<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Role;
use App\Models\RememberToken;
use App\Models\User;

final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    private static bool $resolved = false;

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }

        self::$resolved = true;
        $id = Session::get('auth_user_id');

        if ($id !== null) {
            $user = User::find((int) $id);

            if ($user !== null && $user['status'] === 'Active') {
                self::$user = $user;

                return self::$user;
            }

            Session::forget('auth_user_id');
        }

        $rememberedId = RememberToken::validate($_COOKIE[(string) config('app.remember.cookie', 'beacon_remember')] ?? null);

        if ($rememberedId !== null) {
            $user = User::find($rememberedId);

            if ($user !== null && $user['status'] === 'Active') {
                Session::regenerate();
                Session::put('auth_user_id', (int) $user['id']);
                self::$user = $user;

                return self::$user;
            }
        }

        return null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function role(): ?Role
    {
        $user = self::user();

        if ($user === null) {
            return null;
        }

        return Role::tryFrom((string) $user['role']);
    }

    /**
     * @return array{ok: bool, message: string, user?: array<string, mixed>}
     */
    public static function attempt(string $email, string $password, bool $remember = false): array
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

        if ($user['status'] === 'Pending') {
            return [
                'ok' => false,
                'message' => 'This account is still awaiting OSAS verification.',
            ];
        }

        User::recordLogin((int) $user['id']);
        $user['last_login_at'] = date('Y-m-d H:i:s');
        self::login($user, $remember);

        return ['ok' => true, 'message' => 'Signed in.', 'user' => $user];
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::put('auth_user_id', (int) $user['id']);
        Session::put('_token', Str::random(32));
        self::$user = $user;
        self::$resolved = true;

        if ($remember) {
            RememberToken::issue((int) $user['id']);
        }
    }

    public static function logout(): void
    {
        RememberToken::clear($_COOKIE[(string) config('app.remember.cookie', 'beacon_remember')] ?? null);
        Session::forget('auth_user_id');
        Session::flush();
        Session::regenerate();
        self::$user = null;
        self::$resolved = true;
    }

    /** @param array<string, mixed> $user */
    public static function refresh(array $user): void
    {
        self::$user = $user;
        self::$resolved = true;
    }
}
