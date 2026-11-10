<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Container\Container;

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

            $lifetime = max(1, (int) config('app.session.lifetime', 120 * 60));
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.gc_maxlifetime', (string) $lifetime);
            session_save_path($savePath);
            session_name((string) config('app.session.name', 'beacon_session'));
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => base_path('/') ?: '/',
                'secure' => (bool) config('app.session.secure', false),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            @session_start();
        }

        if (!self::$aged) {
            self::$aged = true;
            $_SESSION['_previous_flash'] = $_SESSION['_flash'] ?? [];
            $_SESSION['_flash'] = [];
        }
    }

    private static function isLaravelSessionActive(): bool
    {
        return class_exists(Container::class)
            && Container::getInstance()
            && function_exists('session')
            && session()->isStarted();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::isLaravelSessionActive()) {
            return session()->get($key, $_SESSION[$key] ?? $default);
        }

        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        if (self::isLaravelSessionActive()) {
            session()->put($key, $value);
        }

        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        if (self::isLaravelSessionActive()) {
            return session()->has($key) || array_key_exists($key, $_SESSION);
        }

        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        if (self::isLaravelSessionActive()) {
            session()->forget($key);
        }

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
        if (self::isLaravelSessionActive()) {
            session()->flush();
        }

        $_SESSION = [];
    }

    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (self::isLaravelSessionActive()) {
            session()->regenerate($deleteOldSession);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id($deleteOldSession);
        }
    }

    public static function flash(string $key, mixed $value): void
    {
        if (self::isLaravelSessionActive()) {
            session()->flash($key, $value);
        }

        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        if (self::isLaravelSessionActive() && session()->has($key)) {
            return session()->get($key);
        }

        $flash = $_SESSION['_previous_flash'] ?? [];

        return array_key_exists($key, $flash) ? $flash[$key] : $default;
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = self::getFlash($key, $default);
        if (self::isLaravelSessionActive()) {
            session()->forget($key);
        }
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
        if (self::isLaravelSessionActive()) {
            session()->flashInput($input);
        }
        self::flash('old', $input);
    }

    public static function old(string $key, mixed $default = null): mixed
    {
        if (self::isLaravelSessionActive() && session()->hasOldInput($key)) {
            return session()->getOldInput($key);
        }

        $old = self::getFlash('old', []);

        return $old[$key] ?? $default;
    }
}
