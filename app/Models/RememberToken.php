<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Str;
use Illuminate\Support\Facades\DB;

final class RememberToken
{
    public static function issue(int $userId): void
    {
        $selector = Str::random(9);
        $validator = Str::random(32);
        $expiresAt = date('Y-m-d H:i:s', time() + (int) config('app.remember.lifetime', 2592000));

        DB::table('remember_tokens')->insert([
            'user_id' => $userId,
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);

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

        $row = DB::table('remember_tokens')->where('selector', $selector)->first();
        $row = $row === null ? null : (array) $row;

        if ($row === null || strtotime((string) $row['expires_at']) < time()) {
            if ($row !== null) {
                DB::table('remember_tokens')->where('selector', $selector)->delete();
            }

            return null;
        }

        if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
            DB::table('remember_tokens')->where('selector', $selector)->delete();

            return null;
        }

        DB::table('remember_tokens')->where('selector', $selector)->update([
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);
        DB::table('remember_tokens')->where('selector', $selector)->delete();
        self::issue((int) $row['user_id']);

        return (int) $row['user_id'];
    }

    public static function clear(?string $cookie): void
    {
        if ($cookie !== null && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            DB::table('remember_tokens')->where('selector', $selector)->delete();
        }

        self::setCookie('', time() - 3600);
    }

    public static function clearForUser(int $userId): void
    {
        DB::table('remember_tokens')->where('user_id', $userId)->delete();
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
