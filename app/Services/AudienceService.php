<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Support\DB;

final class AudienceService
{
    /** @return list<array<string, mixed>> */
    public static function recipients(string $audience): array
    {
        $audience = trim($audience);
        $sql = 'SELECT * FROM users WHERE status = "Active"';
        $bindings = [];

        if ($audience === 'Faculty & Staff') {
            $sql .= ' AND role <> "Student"';
        } else {
            $sql .= ' AND role = "Student"';

            if ($audience === 'Incoming Freshmen') {
                $sql .= ' AND year_level = ?';
                $bindings[] = 'First Year';
            } elseif ($audience === 'Graduating Students') {
                $sql .= ' AND year_level IN (?, ?)';
                $bindings[] = 'Fourth Year';
                $bindings[] = 'Graduate';
            }
        }

        return DB::select($sql . ' ORDER BY id ASC', $bindings);
    }

    public static function notify(
        string $audience,
        string $title,
        string $message,
        string $type = 'info',
        string $entityType = '',
        string $entityId = '',
    ): int {
        $count = 0;

        foreach (self::recipients($audience) as $recipient) {
            NotificationService::send((int) $recipient['id'], $title, $message, $type, $entityType, $entityId);
            $count++;
        }

        return $count;
    }

    /** @return array<string, mixed>|null */
    public static function recipientById(int $id): ?array
    {
        return User::find($id);
    }

    public static function label(string $audience): string
    {
        return match ($audience) {
            'All Student Portals' => 'All student portals',
            'All Students' => 'All students',
            'Incoming Freshmen' => 'Incoming freshmen',
            'Graduating Students' => 'Graduating students',
            'Student Officers' => 'Student officers',
            'Faculty & Staff' => 'Faculty and staff',
            default => $audience,
        };
    }
}
