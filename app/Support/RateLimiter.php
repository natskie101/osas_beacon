<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimal file-based rate limiter.
 *
 * Counters are stored as small JSON files under storage/framework/cache so the
 * limiter works in both the Laravel and standalone execution paths without any
 * schema changes. Only call this for low-volume abuse control (AI endpoints,
 * password resets, registration) — not for hot paths.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        return self::attempts($key) >= $maxAttempts;
    }

    public static function attempts(string $key): int
    {
        $state = self::read($key);

        if ($state['expires_at'] < time()) {
            return 0;
        }

        return $state['count'];
    }

    public static function hit(string $key, int $decaySeconds): void
    {
        $state = self::read($key);
        $now = time();

        if ($state['expires_at'] < $now) {
            $state = ['count' => 0, 'expires_at' => $now + max(1, $decaySeconds)];
        }

        $state['count']++;

        @file_put_contents(self::path($key), json_encode($state), LOCK_EX);
    }

    public static function clear(string $key): void
    {
        @unlink(self::path($key));
    }

    public static function availableIn(string $key): int
    {
        return max(0, self::read($key)['expires_at'] - time());
    }

    /** @return array{count: int, expires_at: int} */
    private static function read(string $key): array
    {
        $content = @file_get_contents(self::path($key));

        if ($content === false) {
            return ['count' => 0, 'expires_at' => 0];
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return ['count' => 0, 'expires_at' => 0];
        }

        return [
            'count' => (int) ($data['count'] ?? 0),
            'expires_at' => (int) ($data['expires_at'] ?? 0),
        ];
    }

    private static function path(string $key): string
    {
        $directory = BASE_PATH . '/storage/framework/cache/rate-limits';

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('The rate limiter storage directory could not be created.');
        }

        return $directory . '/' . sha1($key) . '.json';
    }
}
