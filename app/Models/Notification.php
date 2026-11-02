<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Notification
{
    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        return DB::select(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . $limit,
            [$userId],
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int) DB::value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL',
            [$userId],
        );
    }

    public static function markRead(int $id, int $userId): void
    {
        DB::update(
            'UPDATE notifications SET read_at = ? WHERE id = ? AND user_id = ? AND read_at IS NULL',
            [date('Y-m-d H:i:s'), $id, $userId],
        );
    }

    public static function markAllRead(int $userId): void
    {
        DB::update(
            'UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL',
            [date('Y-m-d H:i:s'), $userId],
        );
    }
}
