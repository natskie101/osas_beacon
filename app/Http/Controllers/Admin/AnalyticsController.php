<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Participation;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Services\NotificationService;
use App\Support\Exceptions\NotFoundHttpException;
use App\Support\Exceptions\ValidationException;
use Illuminate\Http\Request;

final class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth_user();
        $search = mb_strtolower((string) $request->string('search'));
        $tab = (string) $request->string('tab', 'all');
        $program = (string) $request->string('program');

        $allStudents = Participation::students();
        $students = $allStudents;
        $summary = Participation::summary($allStudents);
        $bulkNotifyCount = count(array_filter(
            $allStudents,
            static fn (array $student): bool => $student['tracking_status'] !== 'Compliant',
        ));

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
            $allStudents,
        ))));
        sort($programs);

        return view('admin/analytics/index', [
            'title' => 'Viewing & Tracking',
            'pageTitle' => config('navigation.meta.admin.analytics.title'),
            'pageSubtitle' => 'Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.',
            'pageKey' => 'analytics',
            'user' => $actor,
            'role' => $actor['role'],
            'students' => $students,
            'bulkNotifyCount' => $bulkNotifyCount,
            'summary' => $summary,
            'mandatoryTotal' => Participation::mandatoryModuleCount(),
            'programs' => $programs,
            'filters' => [
                'search' => (string) $request->string('search'),
                'tab' => $tab,
                'program' => $program,
            ],
        ]);
    }

    public function showStudent(Request $request, string $publicId)
    {
        $actor = auth_user();
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

        $photoFile = self::resolveStudentPhoto((int) $student['id']);

        return view('admin/analytics/show', [
            'title' => 'Student Tracking Logs',
            'pageTitle' => 'View Student Profile & Tracking Logs',
            'pageSubtitle' => trim($student['first_name'] . ' ' . $student['last_name']) . ' Â· ' . $student['public_id'],
            'pageKey' => 'analytics',
            'user' => $actor,
            'role' => $actor['role'],
            'student' => $student,
            'studentPhotoFile' => $photoFile,
            'modules' => $modules,
            'attendance' => $attendance,
            'quizAttempts' => $quizAttempts,
            'completed' => $completed,
            'total' => $total,
            'hours' => $hours,
            'trackingStatus' => $trackingStatus,
        ]);
    }

    /**
     * Streams the tracked student's profile photo. Unlike /profile/photo,
     * which is bound to the signed-in account, this is scoped to the student
     * being inspected and to staff roles only (route middleware).
     */
    public function studentPhoto(Request $_request, string $publicId)
    {
        $student = Participation::findStudent($publicId);

        if ($student === null) {
            throw new NotFoundHttpException('The requested student record could not be found.');
        }

        $file = self::resolveStudentPhoto((int) $student['id']);

        if ($file === null) {
            throw new NotFoundHttpException('The profile photo could not be found.');
        }

        $path = FileStorage::path($file);
        $content = is_file($path) ? file_get_contents($path) : false;

        if ($content === false) {
            throw new NotFoundHttpException('The profile photo could not be found.');
        }

        return response($content, 200, [
            'Content-Type' => (string) $file['mime_type'],
            'Content-Length' => (string) strlen($content),
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** @return array<string, mixed>|null */
    private static function resolveStudentPhoto(int $userId): ?array
    {
        $file = File::latestForEntity('user_profile_photo', (string) $userId);

        if ($file === null || !in_array($file['mime_type'] ?? '', ['image/jpeg', 'image/png'], true)) {
            return null;
        }

        // A row without its stored bytes must not render as a broken image.
        return is_file(FileStorage::path($file)) ? $file : null;
    }

    public function notifyStudent(Request $request, string $publicId)
    {
        $student = Participation::findStudent($publicId);

        if ($student === null) {
            throw new NotFoundHttpException('The requested student record could not be found.');
        }

        $data = $this->validate($request, [
            'message' => 'required|string|max:1000',
            'return_to' => 'nullable|in:list,tracking',
        ]);
        $message = trim((string) $data['message']);

        if ($message === '') {
            throw new ValidationException(
                ['message' => 'Enter a message for the student.'],
                $request->input(),
            );
        }

        NotificationService::send(
            (int) $student['id'],
            'Orientation Progress Update',
            $message,
            'info',
            'orientation_progress',
            (string) $student['public_id'],
        );
        AuditLogger::log(
            'orientation.progress_notified',
            'Orientation Progress Notification Sent',
            'An orientation progress update was sent to ' . trim($student['first_name'] . ' ' . $student['last_name']) . '.',
            [
                'entity_type' => 'orientation_progress',
                'entity_id' => (string) $student['public_id'],
                'target_user_id' => (int) $student['id'],
                'tone' => 'blue',
                'icon' => 'send',
            ],
        );

        session()->flash('status', 'Orientation progress notification sent to ' . trim($student['first_name'] . ' ' . $student['last_name']) . '.');
        $destination = ($data['return_to'] ?? 'list') === 'tracking'
            ? '/admin/analytics/' . $student['public_id']
            : '/admin/analytics';

        return redirect(url($destination));
    }

    public function notifyAllNonCompliant(Request $request)
    {
        $data = $this->validate($request, [
            'message' => 'required|string|max:1000',
        ]);
        $message = trim((string) $data['message']);

        if ($message === '') {
            throw new ValidationException(
                ['message' => 'Enter a message for the students.'],
                $request->input(),
            );
        }

        $recipients = array_values(array_filter(
            Participation::students(),
            static fn (array $student): bool => $student['tracking_status'] !== 'Compliant',
        ));

        if ($recipients === []) {
            session()->flash('status', 'There are no non-compliant students to notify.');

            return redirect(url('/admin/analytics'));
        }

        foreach ($recipients as $student) {
            NotificationService::send(
                (int) $student['id'],
                'Orientation Progress Update',
                $message,
                'info',
                'orientation_progress',
                (string) $student['public_id'],
            );
        }

        AuditLogger::log(
            'orientation.progress_bulk_notified',
            'Bulk Orientation Progress Notifications Sent',
            'An orientation progress update was sent to ' . \count($recipients) . ' non-compliant students.',
            [
                'entity_type' => 'orientation_progress',
                'entity_id' => 'bulk',
                'tone' => 'blue',
                'icon' => 'send',
            ],
        );

        session()->flash('status', 'Orientation progress notification sent to ' . \count($recipients) . ' non-compliant students.');

        return redirect(url('/admin/analytics'));
    }
}
