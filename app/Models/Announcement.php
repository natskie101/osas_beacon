<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

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
        $row = DB::table('announcements')
            ->selectRaw('COUNT(*) AS total,
                    SUM(status = "Published") AS published,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Scheduled") AS scheduled,
                    SUM(status = "Archived") AS archived,
                    COALESCE(SUM(views), 0) AS views')
            ->first();

        return array_map('intval', $row === null ? [] : (array) $row);
    }

    /** @return list<array<string, mixed>> */
    public static function published(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));

        return DB::table('announcements')
            ->where('status', 'Published')
            ->where('audience', '<>', 'Faculty & Staff')
            ->orderByRaw('COALESCE(published_at, created_at) DESC, id DESC')
            ->limit($limit)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public static function publishedCount(): int
    {
        return (int) DB::table('announcements')
            ->where('status', 'Published')
            ->where('audience', '<>', 'Faculty & Staff')
            ->count();
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $publicId): ?array
    {
        $row = DB::table('announcements')
            ->where('public_id', $publicId)
            ->where('status', 'Published')
            ->where('audience', '<>', 'Faculty & Staff')
            ->first();

        return $row === null ? null : (array) $row;
    }

    public static function incrementViews(int $id): void
    {
        DB::table('announcements')->where('id', $id)->increment('views');
    }
}
