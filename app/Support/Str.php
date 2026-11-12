<?php

declare(strict_types=1);

namespace App\Support;

final class Str
{
    public static function random(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function limit(string $value, int $length = 80): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');

        if (mb_strlen($plain) <= $length) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, $length)) . '…';
    }

    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    /**
     * Escape SQL LIKE wildcards so user input is matched literally inside a
     * LIKE pattern (both MySQL and PostgreSQL use backslash as the default
     * escape character). The backslash must be replaced first.
     */
    public static function escapeLike(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
    }
}
