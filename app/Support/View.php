<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): string
    {
        $path = BASE_PATH . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();

        try {
            require $path;

            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public static function page(string $layout, string $view, array $data = []): string
    {
        $content = self::render($view, $data);

        return self::render($layout, array_merge($data, ['content' => $content]));
    }
}
