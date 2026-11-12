<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class OrientationProgress
{
    /** @return array<string, array<string, mixed>> keyed by module ID */
    public static function forUser(int $userId): array
    {
        $rows = DB::table('orientation_progress')
            ->where('user_id', $userId)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row['module_id']] = $row;
        }

        return $result;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $userId, int $moduleId): ?array
    {
        $row = DB::table('orientation_progress')
            ->where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->first();

        return $row === null ? null : (array) $row;
    }

    public static function markViewed(int $userId, int $moduleId): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = self::find($userId, $moduleId);

        if ($existing === null) {
            DB::table('orientation_progress')->insert([
                'user_id' => $userId,
                'module_id' => $moduleId,
                'status' => 'In Progress',
                'progress' => 25,
                'last_viewed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        if ($existing['status'] !== 'Completed') {
            DB::table('orientation_progress')
                ->where('id', (int) $existing['id'])
                ->update([
                    'status' => 'In Progress',
                    'progress' => DB::raw('GREATEST(progress, 25)'),
                    'last_viewed_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    public static function complete(int $userId, int $moduleId): bool
    {
        $now = date('Y-m-d H:i:s');
        $existing = self::find($userId, $moduleId);
        $wasCompleted = $existing !== null && $existing['status'] === 'Completed';

        if ($existing === null) {
            DB::table('orientation_progress')->insert([
                'user_id' => $userId,
                'module_id' => $moduleId,
                'status' => 'Completed',
                'progress' => 100,
                'last_viewed_at' => $now,
                'completed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('orientation_progress')
                ->where('id', (int) $existing['id'])
                ->update([
                    'status' => 'Completed',
                    'progress' => 100,
                    'last_viewed_at' => $now,
                    'completed_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        if (!$wasCompleted) {
            DB::table('orientation_modules')
                ->where('id', $moduleId)
                ->increment('completions');
        }

        return !$wasCompleted;
    }

    /** @return array{completed:int,in_progress:int,not_started:int,total:int,percent:float} */
    public static function summary(int $userId, int $totalModules): array
    {
        $row = DB::table('orientation_progress')
            ->where('user_id', $userId)
            ->selectRaw("SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress")
            ->first();
        $row = $row === null ? [] : (array) $row;
        $completed = (int) ($row['completed'] ?? 0);
        $inProgress = (int) ($row['in_progress'] ?? 0);

        return [
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => max(0, $totalModules - $completed - $inProgress),
            'total' => $totalModules,
            'percent' => $totalModules === 0 ? 0 : round(($completed / $totalModules) * 100, 1),
        ];
    }
}
