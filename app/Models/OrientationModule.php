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
            ->selectRaw("COUNT(*) AS total,
                    SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) AS published,
                    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) AS draft,
                    SUM(CASE WHEN status = 'Archived' THEN 1 ELSE 0 END) AS archived")
            ->first();
        $row = $row === null ? [] : (array) $row;

        // Learner and completion figures come from orientation_progress so
        // they reflect the students who actually opened or finished a module.
        $learners = 0;
        $completions = 0;
        $rateTotal = 0.0;

        $progress = DB::table('orientation_progress as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('u.role', 'Student')
            ->whereIn('u.status', ['Active', 'Inactive'])
            ->selectRaw("p.module_id,
                    COUNT(DISTINCT p.user_id) AS learners,
                    COUNT(DISTINCT CASE WHEN p.status = 'Completed' THEN p.user_id END) AS completions")
            ->groupBy('p.module_id')
            ->get();

        foreach ($progress as $moduleRow) {
            $moduleRow = (array) $moduleRow;
            $moduleLearners = (int) $moduleRow['learners'];
            $moduleCompletions = (int) $moduleRow['completions'];
            $learners += $moduleLearners;
            $completions += $moduleCompletions;
            $rateTotal += $moduleLearners === 0 ? 0.0 : ($moduleCompletions / $moduleLearners) * 100;
        }

        $total = (int) ($row['total'] ?? 0);

        return [
            'total' => $total,
            'published' => (int) ($row['published'] ?? 0),
            'draft' => (int) ($row['draft'] ?? 0),
            'archived' => (int) ($row['archived'] ?? 0),
            'learners' => $learners,
            'completions' => $completions,
            'rate' => $total === 0 ? 0.0 : round($rateTotal / $total, 1),
        ];
    }

    /**
     * Replace the denormalised learner/completion columns with figures
     * computed from orientation_progress for the given module rows.
     *
     * @param list<array<string, mixed>> $modules
     * @return list<array<string, mixed>>
     */
    public static function withProgressStats(array $modules): array
    {
        if ($modules === []) {
            return $modules;
        }

        $ids = array_map(static fn (array $module): int => (int) $module['id'], $modules);

        $rows = DB::table('orientation_progress as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->whereIn('p.module_id', $ids)
            ->where('u.role', 'Student')
            ->whereIn('u.status', ['Active', 'Inactive'])
            ->selectRaw("p.module_id,
                    COUNT(DISTINCT p.user_id) AS learners,
                    COUNT(DISTINCT CASE WHEN p.status = 'Completed' THEN p.user_id END) AS completions")
            ->groupBy('p.module_id')
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $stats[(int) $row['module_id']] = [
                'learners' => (int) $row['learners'],
                'completions' => (int) $row['completions'],
            ];
        }

        foreach ($modules as $index => $module) {
            $stat = $stats[(int) $module['id']] ?? ['learners' => 0, 'completions' => 0];
            $modules[$index]['learners'] = $stat['learners'];
            $modules[$index]['completions'] = $stat['completions'];
            $modules[$index]['completion_rate'] = $stat['learners'] === 0
                ? 0.0
                : round(($stat['completions'] / $stat['learners']) * 100, 1);
        }

        return $modules;
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
