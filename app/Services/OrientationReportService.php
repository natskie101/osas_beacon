<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;

final class OrientationReportService
{
    public static function studentCount(): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM users WHERE role = "Student"');
    }

    /** @return array{rate:float,mandatory:int,students:int,compliant:int,in_progress:int,not_started:int,reports:int,records:int} */
    public static function summary(): array
    {
        $modules = self::moduleProgress();
        $students = self::studentCount();
        $mandatory = count($modules);
        $totalPercent = 0.0;
        $compliant = 0;
        $inProgress = 0;
        $notStarted = 0;
        $records = 0;

        foreach ($modules as $module) {
            $totalPercent += (float) $module['percent'];
            $records += (int) $module['completed'];

            if ($module['percent'] >= 80) {
                $compliant++;
            } elseif ($module['percent'] > 0) {
                $inProgress++;
            } else {
                $notStarted++;
            }
        }

        return [
            'rate' => $mandatory === 0 ? 0.0 : round($totalPercent / $mandatory, 1),
            'mandatory' => $mandatory,
            'students' => $students,
            'compliant' => $compliant,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'reports' => $mandatory,
            'records' => $records,
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function moduleProgress(): array
    {
        $students = self::studentCount();
        $rows = DB::select(
            'SELECT m.*,
                    (SELECT COUNT(*) FROM orientation_progress p
                     JOIN users u ON u.id = p.user_id
                     WHERE p.module_id = m.id AND p.status = "Completed" AND u.role = "Student") AS completed
             FROM orientation_modules m
             WHERE m.mandatory = 1 AND m.status = "Published"
             ORDER BY m.id ASC',
        );

        foreach ($rows as $index => $row) {
            $completed = (int) $row['completed'];
            $percent = $students === 0 ? 0.0 : round(($completed / $students) * 100, 1);
            $rows[$index]['total_students'] = $students;
            $rows[$index]['percent'] = $percent;
            $rows[$index]['compliance'] = self::compliance($percent);
            $rows[$index] = array_merge($rows[$index], self::quizMetrics((int) $row['id'], $students));
        }

        return $rows;
    }

    /** @return array{quiz_status:?string,quiz_attempts:int,quiz_pass_rate:float,quiz_average:float} */
    public static function quizMetrics(int $moduleId, int $students): array
    {
        $quiz = DB::first('SELECT id, status FROM orientation_quizzes WHERE module_id = ? LIMIT 1', [$moduleId]);

        if ($quiz === null) {
            return ['quiz_status' => null, 'quiz_attempts' => 0, 'quiz_pass_rate' => 0.0, 'quiz_average' => 0.0];
        }

        $row = DB::first(
            'SELECT COUNT(*) AS attempts,
                    COALESCE(AVG(percent), 0) AS average,
                    COUNT(DISTINCT CASE WHEN passed = 1 THEN user_id END) AS passed_users
             FROM orientation_quiz_attempts
             WHERE quiz_id = ?',
            [(int) $quiz['id']],
        );

        return [
            'quiz_status' => (string) $quiz['status'],
            'quiz_attempts' => (int) ($row['attempts'] ?? 0),
            'quiz_pass_rate' => $students === 0 ? 0.0 : round(((int) ($row['passed_users'] ?? 0) / $students) * 100, 1),
            'quiz_average' => round((float) ($row['average'] ?? 0), 1),
        ];
    }

    /** @return array<string, mixed>|null */
    public static function module(string $publicId): ?array
    {
        $students = self::studentCount();
        $module = DB::first(
            'SELECT m.*,
                    (SELECT COUNT(*) FROM orientation_progress p
                     JOIN users u ON u.id = p.user_id
                     WHERE p.module_id = m.id AND p.status = "Completed" AND u.role = "Student") AS completed
             FROM orientation_modules m
             WHERE m.public_id = ? AND m.mandatory = 1
             LIMIT 1',
            [$publicId],
        );

        if ($module === null) {
            return null;
        }

        $completed = (int) $module['completed'];
        $module['total_students'] = $students;
        $module['percent'] = $students === 0 ? 0.0 : round(($completed / $students) * 100, 1);
        $module['compliance'] = self::compliance((float) $module['percent']);
        $module = array_merge($module, self::quizMetrics((int) $module['id'], $students));

        return $module;
    }

    /** @return list<array<string, mixed>> */
    public static function completions(int $moduleId): array
    {
        return DB::select(
            'SELECT u.student_id, u.first_name, u.middle_name, u.last_name, u.email,
                    u.program, u.year_level, p.completed_at
             FROM orientation_progress p
             JOIN users u ON u.id = p.user_id
             WHERE p.module_id = ? AND p.status = "Completed" AND u.role = "Student"
             ORDER BY p.completed_at DESC, u.last_name ASC',
            [$moduleId],
        );
    }

    /** @return array{label:string,tone:string} */
    private static function compliance(float $percent): array
    {
        if ($percent < 50) {
            return ['label' => 'Critical', 'tone' => 'badge--high'];
        }

        if ($percent < 80) {
            return ['label' => 'Needs Push', 'tone' => 'badge--pending'];
        }

        return ['label' => 'On Track', 'tone' => 'badge--verified'];
    }
}
