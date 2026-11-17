<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

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
        return DB::table('notifications')->insertGetId([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function unreadCount(int $userId): int
    {
        return Notification::unreadCount($userId);
    }

    /**
     * Drop every notification that points at a record being deleted, so the
     * student bell never hands out a link to something that no longer exists.
     */
    public static function forget(string $entityType, string $entityId): int
    {
        if ($entityType === '' || $entityId === '') {
            return 0;
        }

        return DB::table('notifications')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->delete();
    }
}
