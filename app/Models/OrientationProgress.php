<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class OrientationProgress
{
    /** @return array<string, array<string, mixed>> keyed by module ID */
    public static function forUser(int $userId): array
    {
        $rows = DB::select('SELECT * FROM orientation_progress WHERE user_id = ?', [$userId]);
        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row['module_id']] = $row;
        }

        return $result;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $userId, int $moduleId): ?array
    {
        return DB::first(
            'SELECT * FROM orientation_progress WHERE user_id = ? AND module_id = ? LIMIT 1',
            [$userId, $moduleId],
        );
    }

    public static function markViewed(int $userId, int $moduleId): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = self::find($userId, $moduleId);

        if ($existing === null) {
            DB::insert(
                'INSERT INTO orientation_progress
                    (user_id, module_id, status, progress, last_viewed_at, created_at, updated_at)
                 VALUES (?, ?, "In Progress", 25, ?, ?, ?)',
                [$userId, $moduleId, $now, $now, $now],
            );

            return;
        }

        if ($existing['status'] !== 'Completed') {
            DB::update(
                'UPDATE orientation_progress SET status = "In Progress", progress = GREATEST(progress, 25), last_viewed_at = ?, updated_at = ? WHERE id = ?',
                [$now, $now, (int) $existing['id']],
            );
        }
    }

    public static function complete(int $userId, int $moduleId): bool
    {
        $now = date('Y-m-d H:i:s');
        $existing = self::find($userId, $moduleId);
        $wasCompleted = $existing !== null && $existing['status'] === 'Completed';

        if ($existing === null) {
            DB::insert(
                'INSERT INTO orientation_progress
                    (user_id, module_id, status, progress, last_viewed_at, completed_at, created_at, updated_at)
                 VALUES (?, ?, "Completed", 100, ?, ?, ?, ?)',
                [$userId, $moduleId, $now, $now, $now, $now],
            );
        } else {
            DB::update(
                'UPDATE orientation_progress SET status = "Completed", progress = 100, last_viewed_at = ?, completed_at = ?, updated_at = ? WHERE id = ?',
                [$now, $now, $now, (int) $existing['id']],
            );
        }

        if (!$wasCompleted) {
            DB::update(
                'UPDATE orientation_modules SET completions = completions + 1 WHERE id = ?',
                [$moduleId],
            );
        }

        return !$wasCompleted;
    }

    /** @return array{completed:int,in_progress:int,not_started:int,total:int,percent:float} */
    public static function summary(int $userId, int $totalModules): array
    {
        $row = DB::first(
            'SELECT SUM(status = "Completed") AS completed,
                    SUM(status = "In Progress") AS in_progress
             FROM orientation_progress WHERE user_id = ?',
            [$userId],
        );
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
