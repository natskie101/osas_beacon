<?php

declare(strict_types=1);

namespace App\Services;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if ((string) config('mail.mailer', 'log') === 'log') {
            $line = sprintf(
                "[%s] To: %s\nSubject: %s\n\n%s\n%s\n",
                date('Y-m-d H:i:s'),
                $to,
                $subject,
                $body,
                str_repeat('-', 72),
            );

            $path = (string) config('mail.log_path');
            $directory = dirname($path);

            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            file_put_contents($path, $line, FILE_APPEND | LOCK_EX);

            return true;
        }

        $fromAddress = (string) config('mail.from_address');
        $fromName = (string) config('mail.from_name');
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromAddress . '>',
            'Reply-To: ' . $fromAddress,
        ];

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
}
