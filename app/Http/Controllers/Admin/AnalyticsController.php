<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Models\Participation;
use App\Support\Auth;
use App\Support\Exceptions\NotFoundHttpException;
=======
use App\Models\Tracking;
use App\Support\Auth;
use App\Support\DB;
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
use App\Support\Request;
use App\Support\Response;

final class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = Auth::user();
        $search = mb_strtolower($request->string('search'));
<<<<<<< HEAD
        $tab = $request->string('tab', 'all');
        $program = $request->string('program');

        $students = Participation::students();
        $summary = Participation::summary($students);

        if ($search !== '') {
            $students = array_values(array_filter(
                $students,
                static fn (array $student): bool => str_contains(mb_strtolower((string) $student['first_name'] . ' ' . $student['last_name']), $search)
                    || str_contains(mb_strtolower((string) $student['email']), $search)
                    || str_contains(mb_strtolower((string) $student['student_id']), $search)
                    || str_contains(mb_strtolower((string) $student['program']), $search),
            ));
        }

        if ($program !== '') {
            $students = array_values(array_filter(
                $students,
                static fn (array $student): bool => (string) $student['program'] === $program,
            ));
        }

        if ($tab === 'progress') {
            $students = array_values(array_filter(
                $students,
                static fn (array $student): bool => $student['tracking_status'] === 'In Progress',
            ));
        } elseif ($tab === 'at-risk') {
            $students = array_values(array_filter(
                $students,
                static fn (array $student): bool => $student['tracking_status'] !== 'Compliant',
            ));
        }

        $programs = array_values(array_unique(array_filter(array_map(
            static fn (array $student): string => (string) $student['program'],
            Participation::students(),
        ))));
        sort($programs);
=======
        $type = $request->string('type');
        $rows = Tracking::all('views DESC, id DESC');

        if ($search !== '') {
            $rows = array_values(array_filter(
                $rows,
                static fn (array $row): bool => str_contains(mb_strtolower((string) $row['title']), $search)
                    || str_contains(mb_strtolower((string) $row['type']), $search)
                    || str_contains(mb_strtolower((string) $row['public_id']), $search),
            ));
        }

        if ($type !== '') {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => (string) $row['type'] === $type));
        }

        $types = array_values(array_filter(array_map(
            static fn (array $row): string => (string) $row['type'],
            DB::select('SELECT DISTINCT type FROM tracking WHERE type <> "" ORDER BY type ASC'),
        )));

        $audience = DB::first(
            'SELECT COUNT(*) AS accounts,
                    SUM(role = "Student") AS students,
                    SUM(status = "Active") AS active,
                    SUM(status = "Pending") AS pending
             FROM users',
        );
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37

        return $this->page('layouts.app', 'admin/analytics/index', [
            'title' => 'Viewing & Tracking',
            'pageTitle' => config('navigation.meta.admin.analytics.title'),
<<<<<<< HEAD
            'pageSubtitle' => 'Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.',
            'pageKey' => 'analytics',
            'user' => $actor,
            'role' => $actor['role'],
            'students' => $students,
            'summary' => $summary,
            'mandatoryTotal' => Participation::mandatoryModuleCount(),
            'programs' => $programs,
            'filters' => [
                'search' => $request->string('search'),
                'tab' => $tab,
                'program' => $program,
            ],
        ]);
    }

    public function showStudent(Request $request, string $publicId): Response
    {
        $actor = Auth::user();
        $student = Participation::findStudent($publicId);

        if ($student === null) {
            throw new NotFoundHttpException('The requested student record could not be found.');
        }

        $modules = Participation::mandatoryModulesFor((int) $student['id']);
        $attendance = Participation::attendanceFor((int) $student['id']);
        $quizAttempts = Participation::quizAttemptsFor((int) $student['id']);
        $completed = count(array_filter($modules, static fn (array $module): bool => $module['status'] === 'Completed'));
        $total = count($modules);
        $hours = 0;

        foreach ($attendance as $event) {
            $hours += (int) $event['credit_hours'];
        }

        $trackingStatus = match (true) {
            $total > 0 && $completed >= $total => 'Compliant',
            $completed > 0 => 'In Progress',
            default => 'At Risk',
        };

        return $this->page('layouts.app', 'admin/analytics/show', [
            'title' => 'Student Tracking Logs',
            'pageTitle' => 'View Student Profile & Tracking Logs',
            'pageSubtitle' => trim($student['first_name'] . ' ' . $student['last_name']) . ' · ' . $student['public_id'],
            'pageKey' => 'analytics',
            'user' => $actor,
            'role' => $actor['role'],
            'student' => $student,
            'modules' => $modules,
            'attendance' => $attendance,
            'quizAttempts' => $quizAttempts,
            'completed' => $completed,
            'total' => $total,
            'hours' => $hours,
            'trackingStatus' => $trackingStatus,
=======
            'pageSubtitle' => config('navigation.meta.admin.analytics.subtitle'),
            'pageKey' => 'analytics',
            'user' => $actor,
            'role' => $actor['role'],
            'rows' => $rows,
            'totals' => Tracking::totals(),
            'types' => $types,
            'filters' => ['search' => $request->string('search'), 'type' => $type],
            'audience' => array_map('intval', $audience ?? []),
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
        ]);
    }
}
