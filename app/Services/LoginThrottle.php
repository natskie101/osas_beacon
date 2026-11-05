<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;

final class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 10;

    public static function tooManyAttempts(string $email, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - (self::DECAY_MINUTES * 60));

        $count = (int) DB::value(
            'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip_address = ? AND successful = 0 AND attempted_at >= ?',
            [mb_strtolower(trim($email)), $ip, $since],
        );

        return $count >= self::MAX_ATTEMPTS;
    }

    public static function recordFailure(string $email, string $ip): void
    {
        DB::insert(
            'INSERT INTO login_attempts (email, ip_address, successful, attempted_at) VALUES (?, ?, 0, ?)',
            [mb_strtolower(trim($email)), $ip, date('Y-m-d H:i:s')],
        );

        DB::delete('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    }

    public static function clear(string $email, string $ip): void
    {
        DB::delete('DELETE FROM login_attempts WHERE email = ? AND ip_address = ?', [mb_strtolower(trim($email)), $ip]);
    }
}
