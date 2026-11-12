<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

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
        $row = DB::table('events')
            ->selectRaw("COUNT(*) AS total,
                    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled,
                    SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) AS ongoing,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    COALESCE(SUM(registered), 0) AS registered")
            ->first();

        return array_map('intval', $row === null ? [] : (array) $row);
    }

    /** @return list<array<string, mixed>> */
    public static function upcoming(?int $limit = null): array
    {
        $query = DB::table('events')
            ->whereIn('status', ['Scheduled', 'Ongoing'])
            ->where('end_at', '>=', date('Y-m-d H:i:s'));

        if ($limit !== null) {
            $query->limit(max(1, min(20, $limit)));
        }

        return $query
            ->orderBy('start_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public static function upcomingCount(): int
    {
        return (int) DB::table('events')
            ->whereIn('status', ['Scheduled', 'Ongoing'])
            ->where('end_at', '>=', date('Y-m-d H:i:s'))
            ->count();
    }

    /** @return list<array<string, mixed>> */
    public static function forStudent(): array
    {
        return DB::table('events')
            ->where('status', '<>', 'Cancelled')
            ->where('audience', '<>', 'Faculty & Staff')
            ->orderByRaw("CASE WHEN status IN ('Scheduled', 'Ongoing') THEN 0 ELSE 1 END, start_at ASC, id ASC")
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function findVisible(string $publicId): ?array
    {
        $row = DB::table('events')
            ->where('public_id', $publicId)
            ->where('status', '<>', 'Cancelled')
            ->first();

        return $row === null ? null : (array) $row;
    }
}
