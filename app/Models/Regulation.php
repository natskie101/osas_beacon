<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Regulation extends BaseModel
{
    protected static string $table = 'regulations';
    protected static string $prefix = 'REG';
    protected static array $fillable = [
        'reference_code', 'title', 'category', 'audience', 'status', 'effective_date',
        'review_schedule', 'approval_body', 'views', 'summary', 'body', 'sanctions', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'reference_code', 'title', 'summary', 'body', 'category'];

    /** @return array{total:int,published:int,pending:int,draft:int,archived:int,views:int} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Published") AS published,
                    SUM(status = "Pending Review") AS pending,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Archived") AS archived,
                    COALESCE(SUM(views), 0) AS views
             FROM regulations',
        );

        return array_map('intval', $row ?? []);
    }

    /** @return list<array<string, mixed>> */
    public static function published(): array
    {
        return DB::select(
            'SELECT * FROM regulations WHERE status = "Published" ORDER BY updated_at DESC, id DESC',
        );
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $publicId): ?array
    {
        return DB::first(
            'SELECT * FROM regulations WHERE public_id = ? AND status = "Published" LIMIT 1',
            [$publicId],
        );
    }

    public static function incrementViews(int $id): void
    {
        DB::update('UPDATE regulations SET views = views + 1 WHERE id = ?', [$id]);
    }

    /** @return list<string> */
    public static function categories(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) $row['category'],
            DB::select('SELECT DISTINCT category FROM regulations WHERE category <> "" ORDER BY category ASC'),
        )));
    }
}
