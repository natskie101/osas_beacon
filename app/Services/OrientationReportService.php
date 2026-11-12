<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class OrientationReportService
{
    public static function studentCount(): int
    {
        return DB::table('users')->where('role', 'Student')->count();
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
        $rows = DB::table('orientation_modules as m')
            ->selectRaw("m.*, (SELECT COUNT(*) FROM orientation_progress p JOIN users u ON u.id = p.user_id
                    WHERE p.module_id = m.id AND p.status = 'Completed' AND u.role = 'Student') AS completed")
            ->where('m.mandatory', 1)
            ->where('m.status', 'Published')
            ->orderBy('m.id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();

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
        $quiz = DB::table('orientation_quizzes')
            ->select('id', 'status')
            ->where('module_id', $moduleId)
            ->limit(1)
            ->first();

        if ($quiz === null) {
            return ['quiz_status' => null, 'quiz_attempts' => 0, 'quiz_pass_rate' => 0.0, 'quiz_average' => 0.0];
        }

        $quiz = (array) $quiz;

        $row = DB::table('orientation_quiz_attempts')
            ->selectRaw('COUNT(*) AS attempts,
                    COALESCE(AVG(percent), 0) AS average,
                    COUNT(DISTINCT CASE WHEN passed = 1 THEN user_id END) AS passed_users')
            ->where('quiz_id', (int) $quiz['id'])
            ->first();
        $row = $row === null ? [] : (array) $row;

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
        $module = DB::table('orientation_modules as m')
            ->selectRaw("m.*, (SELECT COUNT(*) FROM orientation_progress p
                    JOIN users u ON u.id = p.user_id
                    WHERE p.module_id = m.id AND p.status = 'Completed' AND u.role = 'Student') AS completed")
            ->where('m.public_id', $publicId)
            ->where('m.mandatory', 1)
            ->limit(1)
            ->first();

        if ($module === null) {
            return null;
        }

        $module = (array) $module;
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
        return DB::table('orientation_progress as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->select('u.student_id', 'u.first_name', 'u.middle_name', 'u.last_name', 'u.email', 'u.program', 'u.year_level', 'p.completed_at')
            ->where('p.module_id', $moduleId)
            ->where('p.status', 'Completed')
            ->where('u.role', 'Student')
            ->orderByRaw('p.completed_at DESC, u.last_name ASC')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
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
