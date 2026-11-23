<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $brevoApiKey = (string) (env('BREVO_API_KEY') ?: config('services.brevo.key', ''));

        if ($brevoApiKey !== '') {
            return self::sendViaBrevoApi($brevoApiKey, $to, $subject, $body);
        }

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

    private static function sendViaBrevoApi(string $apiKey, string $to, string $subject, string $body): bool
    {
        $fromAddress = (string) (config('mail.from.address') ?? config('mail.from_address', 'carlosnath991@gmail.com'));
        $fromName = (string) (config('mail.from.name') ?? config('mail.from_name', 'BEACON OSAS Portal'));

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(15)->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $fromName,
                    'email' => $fromAddress,
                ],
                'to' => [
                    ['email' => $to],
                ],
                'subject' => $subject,
                'textContent' => $body,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Brevo API mail delivery failed: ' . $response->body(), [
                'status' => $response->status(),
                'to' => $to,
                'subject' => $subject,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Brevo API request failed: ' . $e->getMessage(), [
                'to' => $to,
                'subject' => $subject,
                'exception' => $e,
            ]);

            return false;
        }
    }
}
