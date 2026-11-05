<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Announcement extends BaseModel
{
    protected static string $table = 'announcements';
    protected static string $prefix = 'ANN';
    protected static array $fillable = [
        'title', 'category', 'audience', 'status', 'priority', 'summary', 'body', 'attachments',
        'author', 'views', 'published_at', 'scheduled_for', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'title', 'summary', 'body', 'author', 'category'];

    /** @return array{total:int,published:int,draft:int,scheduled:int,archived:int,views:int} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Published") AS published,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Scheduled") AS scheduled,
                    SUM(status = "Archived") AS archived,
                    COALESCE(SUM(views), 0) AS views
             FROM announcements',
        );

        return array_map('intval', $row ?? []);
    }

    /** @return list<array<string, mixed>> */
    public static function published(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));

        return DB::select(
            'SELECT * FROM announcements
             WHERE status = "Published" AND audience <> "Faculty & Staff"
             ORDER BY COALESCE(published_at, created_at) DESC, id DESC
             LIMIT ' . $limit,
        );
    }

    public static function publishedCount(): int
    {
        return (int) DB::value(
            'SELECT COUNT(*) FROM announcements WHERE status = "Published" AND audience <> "Faculty & Staff"',
        );
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $publicId): ?array
    {
        return DB::first(
            'SELECT * FROM announcements
             WHERE public_id = ? AND status = "Published" AND audience <> "Faculty & Staff"
             LIMIT 1',
            [$publicId],
        );
    }

    public static function incrementViews(int $id): void
    {
        DB::update('UPDATE announcements SET views = views + 1 WHERE id = ?', [$id]);
    }
}
