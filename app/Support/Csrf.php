<?php

declare(strict_types=1);

namespace App\Support;

final class Csrf
{
    public static function token(): string
    {
        if (!Session::has('_token')) {
            Session::put('_token', Str::random(32));
        }

        return (string) Session::get('_token');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        return is_string($token)
            && $token !== ''
            && hash_equals(self::token(), $token);
    }
}
