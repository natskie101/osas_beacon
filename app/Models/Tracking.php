<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Tracking extends BaseModel
{
    protected static string $table = 'tracking';
    protected static string $prefix = 'TRK';
    protected static array $fillable = [
        'title', 'type', 'views', 'unique_visitors', 'avg_time', 'engagement', 'last_viewed',
        'trend', 'source_id',
    ];
    protected static array $searchable = ['public_id', 'title', 'type', 'source_id'];

    /** @return array{total_views:int,unique_visitors:int,avg_engagement:float,items:int} */
    public static function totals(): array
    {
        $row = DB::first(
            'SELECT COALESCE(SUM(views), 0) AS total_views,
                    COALESCE(SUM(unique_visitors), 0) AS unique_visitors,
                    COALESCE(AVG(engagement), 0) AS avg_engagement,
                    COUNT(*) AS items
             FROM tracking',
        );

        return [
            'total_views' => (int) ($row['total_views'] ?? 0),
            'unique_visitors' => (int) ($row['unique_visitors'] ?? 0),
            'avg_engagement' => (float) ($row['avg_engagement'] ?? 0),
            'items' => (int) ($row['items'] ?? 0),
        ];
    }

    public static function record(string $title, string $type, string $sourceId): void
    {
        $existing = DB::first('SELECT id, views FROM tracking WHERE source_id = ? LIMIT 1', [$sourceId]);

        if ($existing !== null) {
            DB::update(
                'UPDATE tracking SET views = views + 1, last_viewed = ?, updated_at = ? WHERE id = ?',
                [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), (int) $existing['id']],
            );

            return;
        }

        self::create([
            'title' => $title,
            'type' => $type,
            'views' => 1,
            'unique_visitors' => 1,
            'avg_time' => '0m 00s',
            'engagement' => 0,
            'last_viewed' => date('Y-m-d H:i:s'),
            'trend' => 0,
            'source_id' => $sourceId,
        ]);
    }
}
