<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Container\Container;

final class Csrf
{
    public static function token(): string
    {
        if (class_exists(Container::class) && Container::getInstance() && function_exists('session')) {
            try {
                $session = session();
                if ($session->isStarted()) {
                    return $session->token();
                }
            } catch (\Throwable) {
                // fallback
            }
        }

        if (!session()->has('_token')) {
            session()->put('_token', Str::random(32));
        }

        return (string) session()->get('_token');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        if (class_exists(Container::class) && Container::getInstance() && function_exists('session')) {
            try {
                $session = session();
                if ($session->isStarted()) {
                    return hash_equals($session->token(), $token);
                }
            } catch (\Throwable) {
                // fallback
            }
        }

        return hash_equals(self::token(), $token);
    }
}
