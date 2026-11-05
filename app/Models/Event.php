<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Event extends BaseModel
{
    protected static string $table = 'events';
    protected static string $prefix = 'EVT';
    protected static array $fillable = [
        'title', 'category', 'status', 'start_at', 'end_at', 'venue', 'mode', 'audience',
        'capacity', 'registered', 'description', 'organizer', 'verification', 'attachments', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'title', 'description', 'venue', 'organizer', 'category'];

    /** @return array{total:int,scheduled:int,ongoing:int,completed:int,cancelled:int,registered:int} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Scheduled") AS scheduled,
                    SUM(status = "Ongoing") AS ongoing,
                    SUM(status = "Completed") AS completed,
                    SUM(status = "Cancelled") AS cancelled,
                    COALESCE(SUM(registered), 0) AS registered
             FROM events',
        );

        return array_map('intval', $row ?? []);
    }

    /** @return list<array<string, mixed>> */
    public static function upcoming(?int $limit = null): array
    {
        $sql = 'SELECT * FROM events
                WHERE status IN ("Scheduled", "Ongoing") AND end_at >= ?
                ORDER BY start_at ASC, id ASC';
        $bindings = [date('Y-m-d H:i:s')];

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min(20, $limit));
        }

        return DB::select($sql, $bindings);
    }

    public static function upcomingCount(): int
    {
        return (int) DB::value(
            'SELECT COUNT(*) FROM events WHERE status IN ("Scheduled", "Ongoing") AND end_at >= ?',
            [date('Y-m-d H:i:s')],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function forStudent(): array
    {
        return DB::select(
            'SELECT * FROM events
             WHERE status <> "Cancelled" AND audience <> "Faculty & Staff"
             ORDER BY CASE WHEN status IN ("Scheduled", "Ongoing") THEN 0 ELSE 1 END, start_at ASC, id ASC',
        );
    }

    /** @return array<string, mixed>|null */
    public static function findVisible(string $publicId): ?array
    {
        return DB::first(
            'SELECT * FROM events WHERE public_id = ? AND status <> "Cancelled" LIMIT 1',
            [$publicId],
        );
    }
}
