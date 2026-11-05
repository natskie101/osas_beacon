<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;
use App\Support\Str;

final class RememberToken
{
    public static function issue(int $userId): void
    {
        $selector = Str::random(9);
        $validator = Str::random(32);
        $expiresAt = date('Y-m-d H:i:s', time() + (int) config('app.remember.lifetime', 2592000));

        DB::insert(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at, last_used_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $selector, hash('sha256', $validator), $expiresAt, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')],
        );

        self::setCookie($selector . ':' . $validator, time() + (int) config('app.remember.lifetime', 2592000));
    }

    public static function validate(?string $cookie): ?int
    {
        if ($cookie === null || !str_contains($cookie, ':')) {
            return null;
        }

        [$selector, $validator] = explode(':', $cookie, 2);

        if ($selector === '' || $validator === '') {
            return null;
        }

        $row = DB::first('SELECT * FROM remember_tokens WHERE selector = ? LIMIT 1', [$selector]);

        if ($row === null || strtotime((string) $row['expires_at']) < time()) {
            if ($row !== null) {
                DB::delete('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
            }

            return null;
        }

        if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
            DB::delete('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);

            return null;
        }

        DB::update('UPDATE remember_tokens SET last_used_at = ? WHERE selector = ?', [date('Y-m-d H:i:s'), $selector]);
        DB::delete('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
        self::issue((int) $row['user_id']);

        return (int) $row['user_id'];
    }

    public static function clear(?string $cookie): void
    {
        if ($cookie !== null && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            DB::delete('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
        }

        self::setCookie('', time() - 3600);
    }

    public static function clearForUser(int $userId): void
    {
        DB::delete('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }

    private static function setCookie(string $value, int $expires): void
    {
        setcookie((string) config('app.remember.cookie', 'beacon_remember'), $value, [
            'expires' => $expires,
            'path' => base_path('/') ?: '/',
            'secure' => (bool) config('app.session.secure', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[(string) config('app.remember.cookie', 'beacon_remember')] = $value;
    }
}
