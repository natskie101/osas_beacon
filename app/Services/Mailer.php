<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Mail;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $mailer = (string) config('mail.mailer', 'log');

        if ($mailer === 'log') {
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

        try {
            $fromAddress = (string) config('mail.from_address');
            $fromName = (string) config('mail.from_name');

            Mail::mailer($mailer)->raw($body, static function ($message) use ($to, $subject, $fromAddress, $fromName): void {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
