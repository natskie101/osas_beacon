<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Html;

final class BroadcastService
{
    /**
     * Deliver a broadcast to the selected audience.
     *
     * @param array<string, mixed> $broadcast
     * @return array{recipients:int,delivered:int,emails:int}
     */
    public static function send(array $broadcast): array
    {
        $recipients = AudienceService::recipients((string) $broadcast['audience']);
        $delivered = 0;
        $emails = 0;
        $subject = (string) $broadcast['subject'];
        $message = trim(strip_tags((string) $broadcast['message']));

        foreach ($recipients as $recipient) {
            $delivered++;

            NotificationService::send(
                (int) $recipient['id'],
                $subject,
                Html::summary($message, 500),
                'broadcast',
                'broadcast',
                (string) $broadcast['public_id'],
            );

            if (str_contains((string) $broadcast['channel'], 'Email') && !empty($recipient['email'])) {
                Mailer::send((string) $recipient['email'], $subject, $message);
                $emails++;
            }
        }

        return [
            'recipients' => count($recipients),
            'delivered' => $delivered,
            'emails' => $emails,
        ];
    }
}
