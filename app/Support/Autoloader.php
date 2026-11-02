<?php

declare(strict_types=1);

namespace App\Support;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefixes = [
                'App\\' => BASE_PATH . '/app/',
                'Database\\' => BASE_PATH . '/database/',
            ];

            foreach ($prefixes as $prefix => $basePath) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }

                $relative = substr($class, strlen($prefix));
                $path = $basePath . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

                if (is_file($path)) {
                    require $path;
                }

                return;
            }
        });
    }
}
