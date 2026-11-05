<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Participation
{
    public static function mandatoryModuleCount(): int
    {
        return (int) DB::value(
            'SELECT COUNT(*) FROM orientation_modules WHERE mandatory = 1 AND status = "Published"',
        );
    }

    /**
     * Active student ledger with orientation and event participation metrics.
     *
     * @return list<array<string, mixed>>
     */
    public static function students(): array
    {
        $students = DB::select(
            'SELECT id, public_id, first_name, last_name, email, student_id, program, department, year_level, status
             FROM users
             WHERE role = "Student"
             ORDER BY last_name ASC, first_name ASC',
        );
        $total = self::mandatoryModuleCount();

        $progressRows = DB::select(
            'SELECT p.user_id,
                    SUM(p.status = "Completed") AS completed
             FROM orientation_progress p
             JOIN orientation_modules m ON m.id = p.module_id
             WHERE m.mandatory = 1 AND m.status = "Published"
             GROUP BY p.user_id',
        );
        $progress = [];

        foreach ($progressRows as $row) {
            $progress[(int) $row['user_id']] = (int) $row['completed'];
        }

        $attendanceRows = DB::select(
            'SELECT user_id, COUNT(*) AS events, COALESCE(SUM(credit_hours), 0) AS hours
             FROM event_attendance
             GROUP BY user_id',
        );
        $attendance = [];

        foreach ($attendanceRows as $row) {
            $attendance[(int) $row['user_id']] = [
                'events' => (int) $row['events'],
                'hours' => (int) $row['hours'],
            ];
        }

        $result = [];

        foreach ($students as $student) {
            $userId = (int) $student['id'];
            $completed = $progress[$userId] ?? 0;
            $percent = $total === 0 ? 0.0 : round(($completed / $total) * 100, 1);
            $student['orientation_completed'] = $completed;
            $student['orientation_total'] = $total;
            $student['orientation_percent'] = $percent;
            $student['events_attended'] = $attendance[$userId]['events'] ?? 0;
            $student['credit_hours'] = $attendance[$userId]['hours'] ?? 0;
            $student['tracking_status'] = match (true) {
                $total > 0 && $completed >= $total => 'Compliant',
                $completed > 0 => 'In Progress',
                default => 'At Risk',
            };
            $result[] = $student;
        }

        return $result;
    }

    /** @return array{total:int,compliant:int,in_progress:int,at_risk:int,rate:float,hours:int,events:int} */
    public static function summary(array $students): array
    {
        $total = count($students);
        $completedModules = 0;
        $compliant = 0;
        $inProgress = 0;
        $atRisk = 0;
        $hours = 0;
        $events = 0;

        foreach ($students as $student) {
            $completedModules += (int) $student['orientation_completed'];
            $hours += (int) $student['credit_hours'];
            $events += (int) $student['events_attended'];

            if ($student['tracking_status'] === 'Compliant') {
                $compliant++;
            } elseif ($student['tracking_status'] === 'In Progress') {
                $inProgress++;
            } else {
                $atRisk++;
            }
        }

        $mandatoryTotal = self::mandatoryModuleCount();
        $possible = $total * $mandatoryTotal;

        return [
            'total' => $total,
            'compliant' => $compliant,
            'in_progress' => $inProgress,
            'at_risk' => $atRisk,
            'rate' => $possible === 0 ? 0.0 : round(($completedModules / $possible) * 100, 1),
            'hours' => $hours,
            'events' => $events,
        ];
    }

    /** @return array<string, mixed>|null */
    public static function findStudent(string $publicId): ?array
    {
        return DB::first(
            'SELECT id, public_id, first_name, last_name, email, student_id, program, department, year_level, status
             FROM users
             WHERE public_id = ? AND role = "Student"
             LIMIT 1',
            [$publicId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function mandatoryModulesFor(int $userId): array
    {
        return DB::select(
            'SELECT m.id, m.public_id, m.title, m.category, m.duration_minutes,
                    p.status, p.progress, p.completed_at, p.last_viewed_at
             FROM orientation_modules m
             LEFT JOIN orientation_progress p ON p.module_id = m.id AND p.user_id = ?
             WHERE m.mandatory = 1 AND m.status = "Published"
             ORDER BY m.id ASC',
            [$userId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function attendanceFor(int $userId): array
    {
        return DB::select(
            'SELECT * FROM event_attendance WHERE user_id = ? ORDER BY attended_at DESC, id DESC',
            [$userId],
        );
    }

    /** @return list<array<string, mixed>> */
    public static function quizAttemptsFor(int $userId): array
    {
        return DB::select(
            'SELECT m.public_id AS module_code, m.title AS module_title, m.passing_rate,
                    COUNT(a.id) AS attempts,
                    COALESCE(MAX(a.percent), 0) AS best_score,
                    COALESCE(MAX(a.passed), 0) AS passed,
                    MAX(a.submitted_at) AS last_attempt
             FROM orientation_quizzes q
             JOIN orientation_modules m ON m.id = q.module_id
             LEFT JOIN orientation_quiz_attempts a ON a.quiz_id = q.id AND a.user_id = ?
             GROUP BY q.id, m.public_id, m.title, m.passing_rate
             ORDER BY m.id ASC',
            [$userId],
        );
    }
}
