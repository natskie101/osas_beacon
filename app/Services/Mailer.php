<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $mailer = (string) (config('mail.default') ?? config('mail.mailer', env('APP_ENV') === 'production' ? 'smtp' : 'log'));

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

            return (bool) file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        }

        try {
            $fromAddress = (string) (config('mail.from.address') ?? config('mail.from_address', 'carlosnath991@gmail.com'));
            $fromName = (string) (config('mail.from.name') ?? config('mail.from_name', 'BEACON OSAS Portal'));

            Mail::mailer($mailer)->raw($body, static function ($message) use ($to, $subject, $fromAddress, $fromName): void {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Mail delivery failed: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e,
            ]);

            return false;
        }
    }
}
