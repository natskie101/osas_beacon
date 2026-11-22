<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Remembers the last email address that signed in so the login form can
 * prefill it after logout (password is never stored anywhere).
 *
 * Reads $_COOKIE directly, never $request->cookie(): the framework's
 * EncryptCookies middleware cannot decrypt this raw cookie and silently
 * nulls it - the same reason BeaconSessionGuard reads $_COOKIE.
 */
final class LastEmailCookie
{
    public const NAME = 'beacon_last_email';

    public static function set(string $email): void
    {
        $email = trim($email);

        if ($email === '') {
            return;
        }

        setcookie(self::NAME, $email, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => (bool) config('app.session.secure', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[self::NAME] = $email;
    }

    public static function get(): string
    {
        $value = $_COOKIE[self::NAME] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
