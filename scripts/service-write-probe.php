<?php

declare(strict_types=1);

/**
 * Write-path probe for the service layer (Phase 4b).
 *
 * Exercises every converted service write path with real inserts, asserts the
 * stored rows, and restores the database to its original state so the read
 * snapshots (model-snapshot / service-snapshot) stay comparable.
 *
 * FileStorage::storeUpload() cannot run outside a real HTTP upload
 * (is_uploaded_file), so its insert is verified by parsing the column list
 * from the source file and asserting it against the live table schema.
 *
 * Usage: php scripts/service-write-probe.php
 * Exit codes: 0 = all checks passed, 1 = at least one failure.
 */

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Services\LoginThrottle;
use App\Services\NotificationService;
use App\Services\OrientationQuizService;
use App\Services\UserService;
use App\Support\Exceptions\ForbiddenHttpException;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failures = [];
$completed = false;

register_shutdown_function(static function () use (&$completed): void {
    if (!$completed) {
        // An uncaught exception escaped the probe: never report success.
        exit(1);
    }
});

$check = static function (string $name, bool $ok, string $detail = '') use (&$failures): void {
    echo ($ok ? 'PASS ' : 'FAIL '), $name, $ok || $detail === '' ? '' : ' — ' . $detail, PHP_EOL;

    if (!$ok) {
        $failures[] = $name;
    }
};

/** Run $body, then always execute $cleanup. */
$guarded = static function (callable $body, callable $cleanup): void {
    try {
        $body();
    } finally {
        $cleanup();
    }
};

$isDatetime = static fn (mixed $value): bool => is_string($value)
    && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1;

$loginAttemptsBefore = (int) DB::table('login_attempts')->count();

// ---------------------------------------------------------------------------
// AuditLogger::log — insert, verify every stored column, delete.
// ---------------------------------------------------------------------------

$guarded(static function () use ($check, $isDatetime): void {
    AuditLogger::log('probe.audit', 'Probe Title', 'Probe body', [
        'entity_type' => 'probe',
        'entity_id' => 'PROBE-4B',
        'tone' => 'warning',
        'icon' => 'alert',
        'ip_address' => '192.0.2.77',
        'user_agent' => 'probe-agent',
    ]);

    $row = DB::table('audit_log')
        ->where('action', 'probe.audit')
        ->where('entity_id', 'PROBE-4B')
        ->orderByDesc('id')
        ->first();

    $check('audit.insert', $row !== null, $row === null ? 'row missing' : 'ok');

    if ($row === null) {
        $check('audit.fields', false, 'no row to verify');

        return;
    }

    $fieldsOk = $row->actor_user_id === null
        && $row->actor_name === ''
        && $row->target_user_id === null
        && $row->title === 'Probe Title'
        && $row->text === 'Probe body'
        && $row->tone === 'warning'
        && $row->icon === 'alert'
        && $row->ip_address === '192.0.2.77'
        && $row->user_agent === 'probe-agent'
        && $isDatetime($row->created_at);

    $check('audit.fields', $fieldsOk);
}, static function (): void {
    DB::table('audit_log')->where('action', 'probe.audit')->delete();
});

$check('audit.cleanup', DB::table('audit_log')->where('action', 'probe.audit')->count() === 0);

// ---------------------------------------------------------------------------
// NotificationService::send — insertGetId returns the new id, row verified.
// ---------------------------------------------------------------------------

$guarded(static function () use ($check, $isDatetime): void {
    $id = NotificationService::send(1, 'Probe Notification', 'Probe message', 'info', 'probe', 'PROBE-4B');
    $check('notification.send-id', is_int($id) && $id > 0, 'got ' . var_export($id, true));

    $row = is_int($id) && $id > 0 ? DB::table('notifications')->where('id', $id)->first() : null;
    $check('notification.insert', $row !== null, $row === null ? 'row missing' : 'ok');

    if ($row === null) {
        $check('notification.fields', false, 'no row to verify');

        return;
    }

    $fieldsOk = (int) $row->user_id === 1
        && $row->title === 'Probe Notification'
        && $row->message === 'Probe message'
        && $row->type === 'info'
        && $row->entity_type === 'probe'
        && $row->entity_id === 'PROBE-4B'
        && $row->read_at === null
        && $isDatetime($row->created_at);

    $check('notification.fields', $fieldsOk);
}, static function (): void {
    DB::table('notifications')->where('entity_type', 'probe')->where('entity_id', 'PROBE-4B')->delete();
});

$check(
    'notification.cleanup',
    DB::table('notifications')->where('entity_type', 'probe')->where('entity_id', 'PROBE-4B')->count() === 0,
);

// ---------------------------------------------------------------------------
// LoginThrottle — record / count / block / clear, net-zero for the table.
// ---------------------------------------------------------------------------

$email = 'probe-throttle@example.com';
$ip = '192.0.2.55';

$check('throttle.fresh', LoginThrottle::tooManyAttempts($email, $ip) === false);

LoginThrottle::recordFailure($email, $ip);
$firstCount = DB::table('login_attempts')->where('email', $email)->where('ip_address', $ip)->count();
$check('throttle.record', $firstCount === 1, 'count=' . $firstCount);
$check('throttle.input-normalisation', LoginThrottle::tooManyAttempts('  PROBE-THROTTLE@Example.com  ', $ip) === false);
$check('throttle.below-limit', LoginThrottle::tooManyAttempts($email, $ip) === false);

for ($attempt = 0; $attempt < 4; $attempt++) {
    LoginThrottle::recordFailure($email, $ip);
}

$finalCount = DB::table('login_attempts')->where('email', $email)->where('ip_address', $ip)->count();
$check('throttle.accumulated', $finalCount === 5, 'count=' . $finalCount);
$check('throttle.blocked', LoginThrottle::tooManyAttempts($email, $ip) === true);

LoginThrottle::clear($email, $ip);
$clearedCount = DB::table('login_attempts')->where('email', $email)->where('ip_address', $ip)->count();
$check('throttle.clear', $clearedCount === 0, 'count=' . $clearedCount);
$check('throttle.clear-unblocks', LoginThrottle::tooManyAttempts($email, $ip) === false);

$check(
    'throttle.net-zero',
    (int) DB::table('login_attempts')->count() === $loginAttemptsBefore,
    'before=' . $loginAttemptsBefore . ' after=' . DB::table('login_attempts')->count(),
);

// ---------------------------------------------------------------------------
// FileStorage::delete — row removed, no disk file involved.
// ---------------------------------------------------------------------------

$fileId = DB::table('files')->insertGetId([
    'owner_user_id' => null,
    'entity_type' => 'probe',
    'entity_id' => 'PROBE-4B',
    'original_name' => 'probe.txt',
    'stored_name' => 'probe/nope-4b.txt',
    'mime_type' => 'text/plain',
    'size_bytes' => 0,
    'checksum' => null,
    'created_at' => date('Y-m-d H:i:s'),
]);

$guarded(static function () use ($check, $fileId): void {
    $check('filestorage.insert-fixture', DB::table('files')->where('id', $fileId)->count() === 1);

    FileStorage::delete(['id' => $fileId, 'stored_name' => 'probe/nope-4b.txt']);

    $check('filestorage.delete', DB::table('files')->where('id', $fileId)->count() === 0);
}, static function () use ($fileId): void {
    DB::table('files')->where('id', $fileId)->delete();
});

// ---------------------------------------------------------------------------
// FileStorage::storeUpload — static schema check (needs a real HTTP upload).
// ---------------------------------------------------------------------------

$source = (string) file_get_contents($root . '/app/Services/FileStorage.php');
preg_match('/insertGetId\(\[(.*?)\]\)/s', $source, $match);
$keys = [];

if (isset($match[1])) {
    preg_match_all("/'([a-z_]+)'\s*=>/", $match[1], $keyMatches);
    $keys = $keyMatches[1];
}

$fileColumns = array_map(
    static fn (object $column): string => (string) $column->column_name,
    DB::select(
        "SELECT column_name FROM information_schema.columns"
        . " WHERE table_schema = 'public' AND table_name = 'files'",
    ),
);
$missing = array_values(array_diff($keys, $fileColumns));

$check(
    'filestorage.storeUpload-schema',
    $keys !== [] && $missing === [],
    $keys === [] ? 'insertGetId block not found' : 'missing columns: ' . implode(', ', $missing),
);
$check('filestorage.storeUpload-cast', str_contains($source, 'return (array) $row;'));
$check('filestorage.storeUpload-fetch', str_contains($source, "DB::table('files')->where('id', \$id)->first()"));

// ---------------------------------------------------------------------------
// UserService — transaction commit + rollback, FOR UPDATE archive/restore.
// ---------------------------------------------------------------------------

$probeActor = User::find(1);
$probeAdmin = User::find(9);
$ryanStatusBefore = (string) User::find(2)['status'];
$ryanUpdatedAtBefore = (string) User::find(2)['updated_at'];

// Defensive re-run safety: drop leftovers from a previously failed probe.
DB::table('users')->where('email', 'probe-rollback@example.com')->delete();
DB::table('users')->where('email', 'probe-created@example.com')->delete();

// Rollback: registerStudent() creates the user, then storeUpload() throws.
$registerThrew = false;

try {
    UserService::registerStudent(
        ['first_name' => 'Probe', 'last_name' => 'Rollback', 'email' => 'probe-rollback@example.com'],
        ['error' => UPLOAD_ERR_INI_SIZE],
    );
} catch (Throwable) {
    $registerThrew = true;
}

$check('userservice.register-rollback-threw', $registerThrew);
$check(
    'userservice.register-rollback-no-user',
    User::findByEmail('probe-rollback@example.com') === null,
);

// Commit: create() persists user + audit + notification atomically.
$createdId = 0;
$createdPublicId = '';

$guarded(static function () use ($check, &$createdId, &$createdPublicId, $probeActor): void {
    $target = UserService::create(
        [
            'first_name' => 'Probe',
            'last_name' => 'Created',
            'email' => 'probe-created@example.com',
            'role' => 'Student',
            'status' => 'Active',
        ],
        null,
        $probeActor,
    );

    $createdId = (int) ($target['id'] ?? 0);
    $createdPublicId = (string) ($target['public_id'] ?? '');

    $check('userservice.create', $createdId > 0 && $createdPublicId !== '', 'id=' . $createdId);
    $check('userservice.create-row', $createdId > 0 && User::find($createdId) !== null);
    $check(
        'userservice.create-audit',
        $createdPublicId !== ''
            && DB::table('audit_log')
                ->where('action', 'user.created')
                ->where('entity_id', $createdPublicId)
                ->count() === 1,
    );
    $check(
        'userservice.create-notification',
        $createdId > 0 && DB::table('notifications')->where('user_id', $createdId)->count() === 1,
    );
}, static function () use (&$createdId, &$createdPublicId): void {
    DB::table('notifications')->where('user_id', $createdId)->delete();
    DB::table('audit_log')->where('action', 'user.created')->where('entity_id', $createdPublicId)->delete();
    DB::table('users')->where('id', $createdId)->delete();
    DB::table('users')->where('email', 'probe-created@example.com')->delete();
});

// FOR UPDATE archive / restore cycle on the student account (net-zero).
$guarded(static function () use ($check, $probeActor, $probeAdmin, $ryanStatusBefore): void {
    $archived = UserService::archiveAccount(User::find(2), $probeActor);
    $check('userservice.archive', $archived === true);

    $row = User::find(2);
    $check(
        'userservice.archive-status',
        $row !== null && $row['status'] === 'Archived' && ($row['archived_status'] ?? null) === $ryanStatusBefore,
        $row === null ? 'missing' : 'status=' . $row['status'],
    );

    if ($row !== null) {
        $alreadyArchived = false;

        try {
            UserService::archiveAccount($row, $probeActor);
        } catch (ForbiddenHttpException) {
            $alreadyArchived = true;
        }

        $check('userservice.archive-already-archived', $alreadyArchived);
    } else {
        $check('userservice.archive-already-archived', false, 'no archived row');
    }

    // The coordinator is the only active coordinator: archiving must refuse.
    $lastCoordinator = UserService::archiveAccount(User::find(1), $probeAdmin);
    $check('userservice.archive-last-coordinator', $lastCoordinator === false);
    $check('userservice.archive-last-coordinator-untouched', (string) User::find(1)['status'] === 'Active');

    UserService::restoreAccount(User::find(2), $probeActor);
    $restored = User::find(2);
    $check(
        'userservice.restore',
        $restored !== null && $restored['status'] === $ryanStatusBefore && ($restored['archived_status'] ?? null) === null,
        $restored === null ? 'missing' : 'status=' . $restored['status'],
    );

    $notArchived = false;

    try {
        UserService::restoreAccount(User::find(2), $probeActor);
    } catch (ForbiddenHttpException) {
        $notArchived = true;
    }

    $check('userservice.restore-not-archived', $notArchived);

    // setStatus runs inside a transaction; the status is already Active, so
    // only the audit + notification side rows change (cleaned up below).
    UserService::setStatus(
        User::find(2),
        'Active',
        $probeActor,
        'probe.setStatus',
        'Probe Status',
        'Probe status message',
    );
    $check(
        'userservice.setStatus-audit',
        DB::table('audit_log')->where('action', 'probe.setStatus')->count() === 1,
    );
    $check(
        'userservice.setStatus-notification',
        DB::table('notifications')
            ->where('entity_type', 'user')
            ->where('entity_id', 'USR-1002')
            ->where('title', 'Probe Status')
            ->count() === 1,
    );
}, static function () use ($ryanUpdatedAtBefore): void {
    // Net-zero: restore the student account (including updated_at, which the
    // archive/restore/setStatus writes re-stamp) and remove probe side rows.
    DB::table('users')->where('id', 2)->update([
        'status' => 'Active',
        'archived_status' => null,
        'updated_at' => $ryanUpdatedAtBefore,
    ]);
    DB::table('audit_log')->where('action', 'probe.setStatus')->delete();
    DB::table('audit_log')->where('action', 'user.archived')->where('target_user_id', 2)->delete();
    DB::table('audit_log')->where('action', 'user.restored')->where('target_user_id', 2)->delete();
    DB::table('notifications')
        ->where('entity_type', 'user')
        ->where('entity_id', 'USR-1002')
        ->where('title', 'Probe Status')
        ->delete();
});

$check('userservice.net-zero-status', (string) User::find(2)['status'] === $ryanStatusBefore);
$check('userservice.net-zero-updated-at', (string) User::find(2)['updated_at'] === $ryanUpdatedAtBefore);

// ---------------------------------------------------------------------------
// OrientationQuizService — full lifecycle against real rows (net-zero).
// ---------------------------------------------------------------------------

$preexistingQuiz = OrientationQuizService::findForModule(512);
$check(
    'quiz.precondition-empty',
    $preexistingQuiz === null,
    $preexistingQuiz === null ? '' : 'unexpected quiz for module 512: ' . ($preexistingQuiz['title'] ?? '?'),
);

if ($preexistingQuiz === null) {
    $quizId = 0;
    $q1Id = 0;
    $q2Id = 0;
    $attemptIds = [];

    $guarded(static function () use ($check, &$quizId, &$q1Id, &$q2Id, &$attemptIds): void {
        $moduleId = 512;

        $quizId = OrientationQuizService::saveSettings($moduleId, [
            'title' => 'Probe Quiz',
            'instructions' => 'Probe instructions',
            'time_limit_minutes' => 10,
            'status' => 'Draft',
        ], 1);
        $check('quiz.saveSettings-insert', is_int($quizId) && $quizId > 0, 'id=' . var_export($quizId, true));

        $quiz = OrientationQuizService::findForModule($moduleId);
        $check(
            'quiz.findForModule',
            $quiz !== null && (int) $quiz['id'] === $quizId && $quiz['title'] === 'Probe Quiz',
        );

        $byPublicId = OrientationQuizService::findByModulePublicId('ORIENT-2026-001');
        $check(
            'quiz.findByModulePublicId',
            $byPublicId !== null
                && (int) $byPublicId['id'] === $quizId
                && ($byPublicId['module_code'] ?? null) === 'ORIENT-2026-001'
                && ($byPublicId['module_title'] ?? null) !== null
                && array_key_exists('passing_rate', $byPublicId)
                && array_key_exists('verification_method', $byPublicId),
        );

        $updateId = OrientationQuizService::saveSettings($moduleId, [
            'title' => 'Probe Quiz v2',
            'instructions' => 'Probe instructions v2',
            'time_limit_minutes' => 15,
            'status' => 'Published',
        ], 1);
        $check('quiz.saveSettings-update', $updateId === $quizId, 'got ' . var_export($updateId, true));

        $quiz = OrientationQuizService::findForModule($moduleId);
        $check(
            'quiz.settings-updated',
            $quiz !== null
                && $quiz['title'] === 'Probe Quiz v2'
                && (int) $quiz['time_limit_minutes'] === 15
                && $quiz['status'] === 'Published',
        );

        // Questions + options (insert path, position = MAX(position) + 1).
        $q1Id = OrientationQuizService::saveQuestion($quizId, [
            'prompt' => 'Probe Q1',
            'points' => 5,
            'options' => ['Alpha', 'Beta'],
            'correct' => 0,
        ]);
        $q2Id = OrientationQuizService::saveQuestion($quizId, [
            'prompt' => 'Probe Q2',
            'points' => 3,
            'options' => ['One', 'Two', 'Three'],
            'correct' => 2,
        ]);
        $check('quiz.saveQuestion-insert', $q1Id > 0 && $q2Id > 0 && $q1Id !== $q2Id, "q1=$q1Id q2=$q2Id");

        $questions = OrientationQuizService::questions($quizId);
        $check(
            'quiz.questions',
            count($questions) === 2
                && (int) $questions[0]['position'] === 1
                && (int) $questions[1]['position'] === 2
                && count($questions[0]['options']) === 2
                && count($questions[1]['options']) === 3
                && $questions[1]['options'][2]['label'] === 'Three'
                && (int) $questions[1]['options'][2]['is_correct'] === 1,
            'count=' . count($questions),
        );

        $single = OrientationQuizService::question($q1Id, $quizId);
        $check(
            'quiz.question',
            $single !== null && $single['prompt'] === 'Probe Q1' && count($single['options']) === 2,
        );
        $check('quiz.question-wrong-quiz', OrientationQuizService::question($q1Id, 999999) === null);
        $check('quiz.options', count(OrientationQuizService::options($q1Id)) === 2);

        // Update path: prompt/points updated, options replaced wholesale.
        $revised = OrientationQuizService::saveQuestion($quizId, [
            'prompt' => 'Probe Q1 revised',
            'points' => 5,
            'options' => ['A', 'B', 'C'],
            'correct' => 1,
        ], $q1Id);
        $check('quiz.saveQuestion-update', $revised === $q1Id, 'got ' . var_export($revised, true));

        $questions = OrientationQuizService::questions($quizId);
        $check(
            'quiz.options-replaced',
            count($questions) === 2
                && $questions[0]['prompt'] === 'Probe Q1 revised'
                && count($questions[0]['options']) === 3
                && (int) $questions[0]['options'][1]['is_correct'] === 1,
        );

        // Attempts: start -> read -> submit -> read -> second attempt -> expire.
        $attempt1 = OrientationQuizService::startAttempt(['id' => $quizId, 'time_limit_minutes' => 15], 2);
        $attemptIds[] = $attempt1;
        $check('quiz.startAttempt', is_int($attempt1) && $attempt1 > 0, 'id=' . var_export($attempt1, true));

        $active = OrientationQuizService::activeAttempt($quizId, 2);
        $check(
            'quiz.activeAttempt',
            $active !== null && (int) $active['id'] === $attempt1 && $active['status'] === 'In Progress',
        );
        $latest = OrientationQuizService::latestAttempt($quizId, 2);
        $check('quiz.latestAttempt', $latest !== null && (int) $latest['id'] === $attempt1);
        $check('quiz.attempts', count(OrientationQuizService::attempts($quizId, 2)) === 1);
        $check('quiz.attempts-other-user', OrientationQuizService::attempts($quizId, 987654) === []);
        $check('quiz.attempt', OrientationQuizService::attempt($attempt1, 2) !== null);
        $check('quiz.attempt-wrong-user', OrientationQuizService::attempt($attempt1, 1) === null);

        // Score: Q1 correct (5 pts), Q2 wrong (3 pts) -> 5/8 = 62.5%.
        $q1Options = OrientationQuizService::options($q1Id);
        $q2Options = OrientationQuizService::options($q2Id);
        $answers = [$q1Id => (int) $q1Options[1]['id'], $q2Id => (int) $q2Options[0]['id']];
        $quizForSubmit = OrientationQuizService::findByModulePublicId('ORIENT-2026-001');
        $attemptRow = OrientationQuizService::attempt($attempt1, 2);
        $result = OrientationQuizService::submitAttempt($quizForSubmit, $attemptRow, $answers);
        $check(
            'quiz.submitAttempt',
            $result['score'] === 5 && $result['total'] === 8 && $result['percent'] === 62.5,
            json_encode($result),
        );

        $expectedPassed = $result['percent'] >= (float) ($quizForSubmit['passing_rate'] ?? 80);
        $check('quiz.submit-passed-flag', $result['passed'] === $expectedPassed);

        $submitted = OrientationQuizService::attempt($attempt1, 2);
        $check(
            'quiz.attempt-submitted',
            $submitted !== null
                && $submitted['status'] === 'Submitted'
                && (int) $submitted['score'] === 5
                && (int) $submitted['total_points'] === 8
                && (float) $submitted['percent'] === 62.5
                && (int) $submitted['passed'] === ($expectedPassed ? 1 : 0),
        );
        $check('quiz.activeAttempt-after-submit', OrientationQuizService::activeAttempt($quizId, 2) === null);

        $answerRows = OrientationQuizService::attemptAnswers($attempt1);
        $check(
            'quiz.attemptAnswers',
            count($answerRows) === 2
                && $answerRows[0]['prompt'] === 'Probe Q1 revised'
                && ($answerRows[0]['question_code'] ?? null) !== null
                && (int) $answerRows[0]['is_correct'] === 1
                && (int) $answerRows[1]['is_correct'] === 0,
            'count=' . count($answerRows),
        );

        $attempt2 = OrientationQuizService::startAttempt(['id' => $quizId, 'time_limit_minutes' => 15], 2);
        $attemptIds[] = $attempt2;
        $check('quiz.startAttempt-second', is_int($attempt2) && $attempt2 > 0);

        OrientationQuizService::markExpired($attempt2);
        $expired = OrientationQuizService::latestAttempt($quizId, 2);
        $check(
            'quiz.markExpired',
            $expired !== null && (int) $expired['id'] === $attempt2 && $expired['status'] === 'Expired',
        );
        $check('quiz.activeAttempt-after-expire', OrientationQuizService::activeAttempt($quizId, 2) === null);

        OrientationQuizService::deleteQuestion($q1Id, $quizId);
        $afterDelete = OrientationQuizService::questions($quizId);
        $check(
            'quiz.deleteQuestion',
            count($afterDelete) === 1 && (int) $afterDelete[0]['id'] === $q2Id,
            'count=' . count($afterDelete),
        );
        OrientationQuizService::deleteQuestion($q1Id, 999999);
        $check(
            'quiz.deleteQuestion-wrong-quiz',
            count(OrientationQuizService::questions($quizId)) === 1,
        );
    }, static function () use (&$quizId, &$q1Id, &$q2Id, &$attemptIds): void {
        if ($attemptIds !== []) {
            DB::table('orientation_quiz_answers')->whereIn('attempt_id', $attemptIds)->delete();
        }

        DB::table('orientation_quiz_attempts')->where('quiz_id', $quizId)->delete();

        if ($q1Id > 0) {
            DB::table('orientation_quiz_options')->where('question_id', $q1Id)->delete();
        }

        if ($q2Id > 0) {
            DB::table('orientation_quiz_options')->where('question_id', $q2Id)->delete();
        }

        DB::table('orientation_quiz_questions')->where('quiz_id', $quizId)->delete();
        DB::table('orientation_quizzes')->where('id', $quizId)->delete();
    });
}

// ---------------------------------------------------------------------------

$completed = true;
echo PHP_EOL;

if ($failures === []) {
    echo 'ALL SERVICE WRITE CHECKS PASSED', PHP_EOL;
    exit(0);
}

echo count($failures), ' FAILURES:', PHP_EOL;

foreach ($failures as $failure) {
    echo '  - ', $failure, PHP_EOL;
}

exit(1);
