<?php

declare(strict_types=1);

namespace App\Support;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $directory): void
    {
        foreach (glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            self::$items[basename($file, '.php')] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return self::$items;
    }
}
