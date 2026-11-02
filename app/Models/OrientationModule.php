<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class OrientationModule extends BaseModel
{
    protected static string $table = 'orientation_modules';
    protected static string $prefix = 'ORIENT';
    protected static array $fillable = [
        'title', 'category', 'status', 'audience', 'version', 'duration_minutes', 'mandatory',
        'learners', 'completions', 'completion_rate', 'description', 'objectives', 'body',
        'department', 'attachments_count', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'title', 'description', 'category', 'body'];

    /** @return array{total:int,published:int,draft:int,archived:int,learners:int,completions:int,rate:float} */
    public static function counts(): array
    {
        $row = DB::first(
            'SELECT COUNT(*) AS total,
                    SUM(status = "Published") AS published,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Archived") AS archived,
                    COALESCE(SUM(learners), 0) AS learners,
                    COALESCE(SUM(completions), 0) AS completions,
                    COALESCE(AVG(completion_rate), 0) AS rate
             FROM orientation_modules',
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'published' => (int) ($row['published'] ?? 0),
            'draft' => (int) ($row['draft'] ?? 0),
            'archived' => (int) ($row['archived'] ?? 0),
            'learners' => (int) ($row['learners'] ?? 0),
            'completions' => (int) ($row['completions'] ?? 0),
            'rate' => (float) ($row['rate'] ?? 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function published(): array
    {
        return DB::select(
            'SELECT * FROM orientation_modules WHERE status = "Published" ORDER BY mandatory DESC, category ASC, id ASC',
        );
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $publicId): ?array
    {
        return DB::first(
            'SELECT * FROM orientation_modules WHERE public_id = ? AND status = "Published" LIMIT 1',
            [$publicId],
        );
    }

    public static function incrementViews(int $id): void
    {
        DB::update(
            'UPDATE orientation_modules SET learners = learners + 1 WHERE id = ?',
            [$id],
        );
    }

    /** @return list<string> */
    public static function categories(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) $row['category'],
            DB::select('SELECT DISTINCT category FROM orientation_modules WHERE category <> "" ORDER BY category ASC'),
        )));
    }
}
