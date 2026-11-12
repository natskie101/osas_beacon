<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class OrientationModule extends BaseModel
{
    protected static string $table = 'orientation_modules';
    protected static string $prefix = 'ORIENT';
    protected static array $fillable = [
        'title', 'category', 'content_type', 'status', 'audience', 'version', 'duration_minutes',
        'verification_method', 'passing_rate', 'compliance_deadline', 'mandatory',
        'learners', 'completions', 'completion_rate', 'description', 'objectives', 'body',
        'department', 'attachments_count', 'media_file_id', 'video_url', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'title', 'description', 'category', 'body'];

    /** @return array{total:int,published:int,draft:int,archived:int,learners:int,completions:int,rate:float} */
    public static function counts(): array
    {
        $row = DB::table('orientation_modules')
            ->selectRaw('COUNT(*) AS total,
                    SUM(status = "Published") AS published,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Archived") AS archived,
                    COALESCE(SUM(learners), 0) AS learners,
                    COALESCE(SUM(completions), 0) AS completions,
                    COALESCE(AVG(completion_rate), 0) AS rate')
            ->first();
        $row = $row === null ? [] : (array) $row;

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
        return DB::table('orientation_modules')
            ->where('status', 'Published')
            ->orderBy('mandatory', 'desc')
            ->orderBy('category')
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $publicId): ?array
    {
        $row = DB::table('orientation_modules')
            ->where('public_id', $publicId)
            ->where('status', 'Published')
            ->first();

        return $row === null ? null : (array) $row;
    }

    public static function incrementViews(int $id): void
    {
        DB::table('orientation_modules')->where('id', $id)->increment('learners');
    }

    /** @return list<string> */
    public static function categories(): array
    {
        $rows = DB::table('orientation_modules')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        return array_values(array_filter(array_map(
            static fn (string $value): string => (string) $value,
            $rows,
        )));
    }
}
