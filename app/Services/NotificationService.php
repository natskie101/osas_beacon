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
}
