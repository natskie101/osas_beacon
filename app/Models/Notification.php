<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class Notification
{
    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        return DB::table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public static function unreadCount(int $userId): int
    {
        return (int) DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public static function markRead(int $id, int $userId): void
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => date('Y-m-d H:i:s')]);
    }

    public static function markAllRead(int $userId): void
    {
        DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => date('Y-m-d H:i:s')]);
    }
}
