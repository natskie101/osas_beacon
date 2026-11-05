<?php

declare(strict_types=1);

namespace App\Support;

final class Session
{
    private static bool $aged = false;

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $savePath = BASE_PATH . '/storage/framework/sessions';

            if (!is_dir($savePath)) {
                mkdir($savePath, 0775, true);
            }

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            session_save_path($savePath);
            session_name((string) config('app.session.name', 'beacon_session'));
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => base_path('/') ?: '/',
                'secure' => (bool) config('app.session.secure', false),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        if (!self::$aged) {
            self::$aged = true;
            $_SESSION['_previous_flash'] = $_SESSION['_flash'] ?? [];
            $_SESSION['_flash'] = [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);

        return $value;
    }

    public static function flush(): void
    {
        $_SESSION = [];
    }

    public static function regenerate(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $flash = $_SESSION['_previous_flash'] ?? [];

        return array_key_exists($key, $flash) ? $flash[$key] : $default;
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = self::getFlash($key, $default);
        unset($_SESSION['_previous_flash'][$key]);

        return $value;
    }

    /** @param array<string, string> $errors */
    public static function flashErrors(array $errors): void
    {
        self::flash('errors', $errors);
    }

    /** @return array<string, string> */
    public static function errors(): array
    {
        return self::getFlash('errors', []);
    }

    public static function flashOld(array $input): void
    {
        unset($input['password'], $input['password_confirmation'], $input['current_password'], $input['_token']);
        self::flash('old', $input);
    }

    public static function old(string $key, mixed $default = null): mixed
    {
        $old = self::getFlash('old', []);

        return $old[$key] ?? $default;
    }
}
