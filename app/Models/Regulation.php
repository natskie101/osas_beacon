<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class Regulation extends BaseModel
{
    protected static string $table = 'regulations';
    protected static string $prefix = 'REG';
    protected static array $fillable = [
        'reference_code', 'title', 'category', 'severity', 'audience', 'status', 'effective_date',
        'review_schedule', 'approval_body', 'views', 'summary', 'body', 'sanctions', 'created_by',
    ];
    protected static array $searchable = ['public_id', 'reference_code', 'title', 'summary', 'body', 'category'];

    /** @return array{total:int,published:int,pending:int,draft:int,archived:int,views:int} */
    public static function counts(): array
    {
        $row = DB::table('regulations')
            ->selectRaw('COUNT(*) AS total,
                    SUM(status = "Published") AS published,
                    SUM(status = "Pending Review") AS pending,
                    SUM(status = "Draft") AS draft,
                    SUM(status = "Archived") AS archived,
                    COALESCE(SUM(views), 0) AS views')
            ->first();

        return array_map('intval', $row === null ? [] : (array) $row);
    }

    /** @return list<array<string, mixed>> */
    public static function published(): array
    {
        return DB::table('regulations')
            ->where('status', 'Published')
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $publicId): ?array
    {
        $row = DB::table('regulations')
            ->where('public_id', $publicId)
            ->where('status', 'Published')
            ->first();

        return $row === null ? null : (array) $row;
    }

    public static function incrementViews(int $id): void
    {
        DB::table('regulations')->where('id', $id)->increment('views');
    }

    /** @return list<string> */
    public static function categories(): array
    {
        $rows = DB::table('regulations')
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

    /** @return array<string, int> category => published count */
    public static function publishedCategoryCounts(): array
    {
        $rows = DB::table('regulations')
            ->where('status', 'Published')
            ->where('category', '<>', '')
            ->select('category')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('category')
            ->orderBy('category')
            ->get();
        $counts = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $counts[(string) $row['category']] = (int) $row['total'];
        }

        return $counts;
    }
}
