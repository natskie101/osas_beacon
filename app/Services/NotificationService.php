<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Support\DB;

final class NotificationService
{
    public static function send(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        string $entityType = '',
        string $entityId = '',
    ): int {
        return DB::insert(
            'INSERT INTO notifications (user_id, title, message, type, entity_type, entity_id, read_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NULL, ?)',
            [$userId, $title, $message, $type, $entityType, $entityId, date('Y-m-d H:i:s')],
        );
    }

    public static function unreadCount(int $userId): int
    {
        return Notification::unreadCount($userId);
    }
}
