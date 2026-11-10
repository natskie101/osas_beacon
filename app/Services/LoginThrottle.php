<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 10;

    public static function tooManyAttempts(string $email, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - (self::DECAY_MINUTES * 60));

        $count = DB::table('login_attempts')
            ->where('email', mb_strtolower(trim($email)))
            ->where('ip_address', $ip)
            ->where('successful', 0)
            ->where('attempted_at', '>=', $since)
            ->count();

        return $count >= self::MAX_ATTEMPTS;
    }

    public static function recordFailure(string $email, string $ip): void
    {
        DB::table('login_attempts')->insert([
            'email' => mb_strtolower(trim($email)),
            'ip_address' => $ip,
            'successful' => 0,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('login_attempts')
            ->where('attempted_at', '<', date('Y-m-d H:i:s', time() - 86400))
            ->delete();
    }

    public static function clear(string $email, string $ip): void
    {
        DB::table('login_attempts')
            ->where('email', mb_strtolower(trim($email)))
            ->where('ip_address', $ip)
            ->delete();
    }
}
