<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;

final class Participation
{
    public static function mandatoryModuleCount(): int
    {
        return (int) DB::table('orientation_modules')
            ->where('mandatory', 1)
            ->where('status', 'Published')
            ->count();
    }

    /**
     * Active student ledger with orientation and event participation metrics.
     *
     * @return list<array<string, mixed>>
     */
    public static function students(): array
    {
        $students = DB::table('users')
            ->select(
                'id',
                'public_id',
                'first_name',
                'last_name',
                'email',
                'student_id',
                'program',
                'department',
                'year_level',
                'status',
            )
            ->where('role', 'Student')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
        $total = self::mandatoryModuleCount();

        $progressRows = DB::table('orientation_progress as p')
            ->join('orientation_modules as m', 'm.id', '=', 'p.module_id')
            ->where('m.mandatory', 1)
            ->where('m.status', 'Published')
            ->select('p.user_id')
            ->selectRaw("SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) AS completed")
            ->groupBy('p.user_id')
            ->get();
        $progress = [];

        foreach ($progressRows as $row) {
            $row = (array) $row;
            $progress[(int) $row['user_id']] = (int) $row['completed'];
        }

        $attendanceRows = DB::table('event_attendance')
            ->select('user_id')
            ->selectRaw('COUNT(*) AS events, COALESCE(SUM(credit_hours), 0) AS hours')
            ->groupBy('user_id')
            ->get();
        $attendance = [];

        foreach ($attendanceRows as $row) {
            $row = (array) $row;
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
        $row = DB::table('users')
            ->select(
                'id',
                'public_id',
                'first_name',
                'last_name',
                'email',
                'student_id',
                'program',
                'department',
                'year_level',
                'status',
            )
            ->where('public_id', $publicId)
            ->where('role', 'Student')
            ->first();

        return $row === null ? null : (array) $row;
    }

    /** @return list<array<string, mixed>> */
    public static function mandatoryModulesFor(int $userId): array
    {
        return DB::table('orientation_modules as m')
            ->leftJoin('orientation_progress as p', static function ($join) use ($userId): void {
                $join->on('p.module_id', '=', 'm.id')
                    ->where('p.user_id', $userId);
            })
            ->where('m.mandatory', 1)
            ->where('m.status', 'Published')
            ->select(
                'm.id',
                'm.public_id',
                'm.title',
                'm.category',
                'm.duration_minutes',
                'p.status',
                'p.progress',
                'p.completed_at',
                'p.last_viewed_at',
            )
            ->orderBy('m.id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function attendanceFor(int $userId): array
    {
        return DB::table('event_attendance')
            ->where('user_id', $userId)
            ->orderBy('attended_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function quizAttemptsFor(int $userId): array
    {
        return DB::table('orientation_quizzes as q')
            ->join('orientation_modules as m', 'm.id', '=', 'q.module_id')
            ->leftJoin('orientation_quiz_attempts as a', static function ($join) use ($userId): void {
                $join->on('a.quiz_id', '=', 'q.id')
                    ->where('a.user_id', $userId);
            })
            ->selectRaw('m.public_id AS module_code, m.title AS module_title, m.passing_rate,
                    COUNT(a.id) AS attempts,
                    COALESCE(MAX(a.percent), 0) AS best_score,
                    COALESCE(MAX(a.passed), 0) AS passed,
                    MAX(a.submitted_at) AS last_attempt')
            ->groupBy('q.id', 'm.id', 'm.public_id', 'm.title', 'm.passing_rate')
            ->orderBy('m.id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }
}
