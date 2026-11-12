<?php

declare(strict_types=1);

/**
 * Write-path probe for the model layer (Phase 4a).
 *
 * Runs create/update/delete cycles against every converted write method,
 * asserts the results, and restores the database to its original state so the
 * golden snapshot stays comparable.
 *
 * Usage: php scripts/model-write-probe.php
 * Exit codes: 0 = all checks passed, 1 = at least one failure.
 */

use App\Models\Announcement;
use App\Models\DocumentRequest;
use App\Models\Notification;
use App\Models\OrientationProgress;
use App\Models\PasswordReset;
use App\Models\RememberToken;
use App\Models\SupportTicket;
use App\Models\Tracking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Buffer every check line so RememberToken::issue()/validate() can call
// setcookie() mid-run without tripping Laravel's "headers already sent" guard.
ob_start();

register_shutdown_function(static function () use (&$completed): void {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }

    if (!$completed) {
        // An uncaught exception escaped the probe: never report success.
        exit(1);
    }
});

$failures = [];
$completed = false;

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

/**
 * Mirror of BaseModel::nextPublicId() over a snapshot of existing rows, so the
 * expected "next" public ID can be computed against whatever is seeded.
 *
 * @param list<string> $existingIds
 */
$nextPublicId = static function (string $prefix, array $existingIds): string {
    $year = (int) date('Y');
    $max = 0;
    $usesYear = null;

    foreach ($existingIds as $publicId) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '-(?:(\d{4})-)?(\d+)$/', (string) $publicId, $matches) !== 1) {
            continue;
        }

        $rowYear = $matches[1] !== '' ? (int) $matches[1] : null;

        if ($rowYear !== null && $rowYear !== $year) {
            continue;
        }

        $number = (int) $matches[2];

        if ($number >= $max) {
            $max = $number;
            $usesYear = $rowYear !== null;
        }
    }

    return sprintf($usesYear === false ? $prefix . '-%03d' : $prefix . '-' . $year . '-%03d', $max + 1);
};

// ---------------------------------------------------------------------------
// Remember tokens: issue -> validate (rotate) -> clear.
// ---------------------------------------------------------------------------

$guarded(static function () use ($check): void {
    RememberToken::issue(1);
    $cookie = (string) ($_COOKIE[(string) config('app.remember.cookie', 'beacon_remember')] ?? '');
    $check('remember.issue', str_contains($cookie, ':'), $cookie === '' ? 'no cookie set' : 'ok');

    $uid = RememberToken::validate($cookie !== '' ? $cookie : null);
    $check('remember.validate', $uid === 1, 'got ' . var_export($uid, true));

    $rotated = (string) ($_COOKIE[(string) config('app.remember.cookie', 'beacon_remember')] ?? '');
    $check('remember.rotated', $rotated !== '' && $rotated !== $cookie);

    $uid2 = RememberToken::validate($rotated !== '' ? $rotated : null);
    $check('remember.validate-rotated', $uid2 === 1, 'got ' . var_export($uid2, true));

    RememberToken::clear($rotated !== '' ? $rotated : null);
}, static function (): void {
    DB::table('remember_tokens')->delete();
});

// ---------------------------------------------------------------------------
// Announcements: BaseModel create / update (touch + no-touch) / delete /
// counts / paginate, driven through the real fillable list.
// ---------------------------------------------------------------------------

$announcementId = null;

$guarded(static function () use ($check, &$announcementId): void {
    $countsBefore = Announcement::counts();

    $announcementId = Announcement::create([
        'title' => 'PROBE announcement',
        'category' => 'General',
        'audience' => 'All Students',
        'status' => 'Draft',
        'priority' => 'Normal',
        'summary' => 'probe summary',
        'body' => 'probe body',
        'created_by' => 1,
    ]);

    $row = Announcement::find($announcementId);
    $check('announcement.create', $row !== null && $row['title'] === 'PROBE announcement');
    $check(
        'announcement.public-id',
        $row !== null && preg_match('/^ANN-\d{4}-\d{3}$/', (string) $row['public_id']) === 1,
        $row === null ? 'missing row' : (string) $row['public_id'],
    );
    $check(
        'announcement.timestamps',
        $row !== null && $row['created_at'] !== '' && $row['updated_at'] !== '',
    );

    Announcement::update($announcementId, ['title' => 'PROBE updated', 'status' => 'Published']);
    $updated = Announcement::find($announcementId);
    $check(
        'announcement.update-touch',
        $updated !== null && $updated['title'] === 'PROBE updated' && $updated['status'] === 'Published',
    );

    $touchStamp = (string) ($updated['updated_at'] ?? '');
    Announcement::update($announcementId, ['summary' => 'probe summary 2'], false);
    $noTouch = Announcement::find($announcementId);
    $check(
        'announcement.update-no-touch',
        $noTouch !== null && $noTouch['summary'] === 'probe summary 2' && $noTouch['updated_at'] === $touchStamp,
        'summary=' . (string) ($noTouch['summary'] ?? 'missing') . ' updated_at=' . (string) ($noTouch['updated_at'] ?? ''),
    );

    $counts = Announcement::counts();
    $check(
        'announcement.counts-includes-row',
        $counts['total'] === $countsBefore['total'] + 1
            && $counts['draft'] === $countsBefore['draft']
            && $counts['published'] === $countsBefore['published'] + 1,
        json_encode($counts),
    );

    $page = Announcement::paginate(['search' => 'PROBE'], 1);
    $check(
        'announcement.paginate-search',
        $page['total'] === 1 && (int) ($page['data'][0]['id'] ?? 0) === $announcementId,
        'total=' . $page['total'],
    );

    $pageAll = Announcement::paginate([], 1);
    $check(
        'announcement.paginate-all',
        $pageAll['total'] === $countsBefore['total'] + 1
            && (int) ($pageAll['data'][0]['id'] ?? 0) === $announcementId,
        'total=' . $pageAll['total'],
    );

    Announcement::delete($announcementId);
    $check('announcement.delete', Announcement::find($announcementId) === null);
    $check('announcement.counts-restored', Announcement::counts()['total'] === $countsBefore['total']);
}, static function () use (&$announcementId): void {
    if ($announcementId !== null) {
        DB::table('announcements')->where('id', $announcementId)->delete();
    }
});

// ---------------------------------------------------------------------------
// Users: create (slug + username collision loop + USR public id),
// password/login updates, counts, then restore.
// ---------------------------------------------------------------------------

$userIds = [];

$guarded(static function () use ($check, &$userIds): void {
    $totalBefore = User::counts()['total'];

    $firstId = User::create([
        'first_name' => 'Probe',
        'middle_name' => null,
        'last_name' => 'Tester',
        'email' => 'probe.tester@uc.edu.ph',
        'role' => 'Student',
        'status' => 'Pending',
        'program' => 'BSIT',
    ]);
    $userIds[] = $firstId;

    $first = User::find($firstId);
    $expectedPublicId = 'USR-' . str_pad((string) (1000 + $firstId), 4, '0', STR_PAD_LEFT);
    $check(
        'user.create',
        $first !== null && $first['public_id'] === $expectedPublicId,
        $first === null ? 'missing row' : $first['public_id'],
    );
    $check('user.create-username-slug', $first !== null && $first['username'] === 'probe-tester', $first['username'] ?? '');
    $check('user.create-counts', User::counts()['total'] === $totalBefore + 1);

    $secondId = User::create([
        'first_name' => 'Probe',
        'middle_name' => null,
        'last_name' => 'Tester',
        'email' => 'probe.tester2@uc.edu.ph',
        'username' => 'probe.tester',
        'role' => 'Student',
        'status' => 'Pending',
        'program' => 'BSIT',
    ]);
    $userIds[] = $secondId;

    $second = User::find($secondId);
    $check('user.username-collision-suffix', $second !== null && $second['username'] === 'probe-tester1', $second['username'] ?? '');

    User::update($firstId, ['status' => 'Active', 'program' => 'BSCT']);
    $reloaded = User::find($firstId);
    $check('user.update', $reloaded !== null && $reloaded['status'] === 'Active' && $reloaded['program'] === 'BSCT');

    User::updatePassword($firstId, 'Probe@12345');
    $reloaded = User::find($firstId);
    $check(
        'user.updatePassword',
        $reloaded !== null && password_verify('Probe@12345', (string) $reloaded['password_hash']),
    );

    User::recordLogin($firstId);
    $reloaded = User::find($firstId);
    $check(
        'user.recordLogin',
        $reloaded !== null && $reloaded['last_login_at'] !== null && $reloaded['last_seen_at'] !== null,
    );

    User::touchSeen($firstId);

    $searchPage = User::paginate(['search' => 'probe'], 1);
    $check('user.paginate-search', $searchPage['total'] === 2, 'total=' . $searchPage['total']);
}, static function () use (&$userIds): void {
    if ($userIds !== []) {
        DB::table('users')->whereIn('id', $userIds)->delete();
    }
});

// ---------------------------------------------------------------------------
// Support tickets: create (next public id against an existing TCS row),
// addMessage / messages / counts.
// ---------------------------------------------------------------------------

$ticketId = null;
$ticketPublicId = '';

$guarded(static function () use ($check, $nextPublicId, &$ticketId, &$ticketPublicId): void {
    $ticketCountBefore = SupportTicket::counts()['total'];
    $expectedPublicId = $nextPublicId('TCS', DB::table('support_tickets')->pluck('public_id')->all());

    $ticketId = SupportTicket::create([
        'student_user_id' => 2,
        'student_name' => 'Ryan Dela Cruz',
        'student_email' => 'ryan.delacruz@uc.edu.ph',
        'student_id' => '2026-0001',
        'program' => 'BSIT',
        'category' => 'Technical',
        'subject' => 'PROBE ticket',
        'description' => 'probe description',
        'priority' => 'Normal',
        'status' => 'Open',
        'submitted_at' => date('Y-m-d H:i:s'),
    ]);

    $ticket = SupportTicket::find($ticketId);
    $ticketPublicId = $ticket === null ? '' : (string) $ticket['public_id'];
    $check(
        'ticket.create-next-public-id',
        $ticket !== null && $ticketPublicId === $expectedPublicId,
        ($ticket === null ? 'missing row' : $ticketPublicId) . ' expected ' . $expectedPublicId,
    );

    $before = SupportTicket::counts()['total'];
    SupportTicket::addMessage($ticketId, 'Probe User', 'Student', 'probe message');
    $check('ticket.addMessage', count(SupportTicket::messages($ticketId)) === 1);
    $check(
        'ticket.counts-includes-row',
        $before === $ticketCountBefore + 1 && SupportTicket::counts()['total'] === $before,
        'before=' . $before,
    );

    $found = SupportTicket::findForStudent($ticketPublicId, 2);
    $check('ticket.findForStudent', $found !== null);
    $check('ticket.findForStudent-wrong-user', SupportTicket::findForStudent($ticketPublicId, 1) === null);
}, static function () use (&$ticketId): void {
    if ($ticketId !== null) {
        DB::table('support_messages')->where('ticket_id', $ticketId)->delete();
        DB::table('support_tickets')->where('id', $ticketId)->delete();
    }
});

// ---------------------------------------------------------------------------
// Document requests: create + timeline event, then restore.
// ---------------------------------------------------------------------------

$requestId = null;
$requestPublicId = '';

$guarded(static function () use ($check, $nextPublicId, &$requestId, &$requestPublicId): void {
    $expectedPublicId = $nextPublicId('DR', DB::table('document_requests')->pluck('public_id')->all());

    $requestId = DocumentRequest::create([
        'student_user_id' => 2,
        'student_name' => 'Ryan Dela Cruz',
        'student_id' => '2026-0001',
        'student_email' => 'ryan.delacruz@uc.edu.ph',
        'program' => 'BSIT',
        'year_level' => '2nd Year',
        'document_type' => 'Certificate of Enrollment',
        'purpose' => 'probe',
        'copies' => 1,
        'delivery_mode' => 'Pick-up',
        'status' => 'Submitted',
        'priority' => 'Normal',
        'requested_at' => date('Y-m-d H:i:s'),
    ]);

    $request = DocumentRequest::find($requestId);
    $requestPublicId = $request === null ? '' : (string) $request['public_id'];
    $check(
        'documentRequest.create-public-id',
        $request !== null && $requestPublicId === $expectedPublicId,
        ($request === null ? 'missing row' : $requestPublicId) . ' expected ' . $expectedPublicId,
    );

    DocumentRequest::addEvent($requestId, 'Submitted', 'Probe User', 'probe note');
    $check('documentRequest.timeline', count(DocumentRequest::timeline($requestId)) === 1);
    $check('documentRequest.findForStudent', DocumentRequest::findForStudent($requestPublicId, 2) !== null);
}, static function () use (&$requestId): void {
    if ($requestId !== null) {
        DB::table('document_request_events')->where('request_id', $requestId)->delete();
        DB::table('document_requests')->where('id', $requestId)->delete();
    }
});

// ---------------------------------------------------------------------------
// Orientation progress: insert path, GREATEST() update, complete + module
// counter, then restore the module counter and delete the probe row.
// ---------------------------------------------------------------------------

// Resolve a module the probed user has no progress row for yet, so the probe
// works against any seeded dataset (net-zero either way).
$probeModule = DB::table('orientation_modules as m')
    ->whereNotExists(static function ($query): void {
        $query->selectRaw('1')
            ->from('orientation_progress as p')
            ->whereColumn('p.module_id', 'm.id')
            ->where('p.user_id', 1);
    })
    ->orderBy('m.id')
    ->first(['m.id', 'm.completions']);

if ($probeModule === null) {
    $probeModule = DB::table('orientation_modules')->orderBy('id')->first(['id', 'completions']);
}

$progressModuleId = $probeModule === null ? 0 : (int) $probeModule->id;
$completionsBefore = $probeModule === null ? 0 : (int) $probeModule->completions;
$summaryBefore = OrientationProgress::summary(1, 5);

$guarded(static function () use ($check, $progressModuleId, $summaryBefore): void {
    $check('progress.precondition-module-exists', $progressModuleId > 0, 'no orientation module available');

    if ($progressModuleId === 0) {
        return;
    }

    OrientationProgress::markViewed(1, $progressModuleId);
    $row = OrientationProgress::find(1, $progressModuleId);
    $check(
        'progress.markViewed-insert',
        $row !== null && $row['status'] === 'In Progress' && (int) $row['progress'] === 25,
        $row === null ? 'missing row' : $row['status'] . '/' . $row['progress'],
    );

    OrientationProgress::markViewed(1, $progressModuleId);
    $row = OrientationProgress::find(1, $progressModuleId);
    $check('progress.markViewed-update', $row !== null && (int) $row['progress'] === 25);

    $wasNew = OrientationProgress::complete(1, $progressModuleId);
    $row = OrientationProgress::find(1, $progressModuleId);
    $check(
        'progress.complete',
        $wasNew === true && $row !== null && $row['status'] === 'Completed' && (int) $row['progress'] === 100,
    );

    $wasAgain = OrientationProgress::complete(1, $progressModuleId);
    $check('progress.complete-idempotent', $wasAgain === false);

    $summary = OrientationProgress::summary(1, 5);
    $check(
        'progress.summary',
        $summary['completed'] === $summaryBefore['completed'] + 1
            && $summary['percent'] === round((($summaryBefore['completed'] + 1) / 5) * 100, 1),
        json_encode($summary),
    );
}, static function () use ($progressModuleId, $completionsBefore): void {
    if ($progressModuleId > 0) {
        DB::table('orientation_progress')->where('user_id', 1)->where('module_id', $progressModuleId)->delete();
        DB::table('orientation_modules')->where('id', $progressModuleId)->update(['completions' => $completionsBefore]);
    }
});

// ---------------------------------------------------------------------------
// Notifications: markRead / markAllRead, then restore read_at.
// ---------------------------------------------------------------------------

$restoredNotifications = [];
$notificationSnapshot = [];

$guarded(static function () use ($check, &$restoredNotifications, &$notificationSnapshot): void {
    // The seeded dataset may ship no notifications at all, so stage one unread
    // row for the probed user and snapshot the pre-existing read states.
    $notificationSnapshot = DB::table('notifications')
        ->where('user_id', 2)
        ->pluck('read_at', 'id')
        ->all();

    $probeNotificationId = DB::table('notifications')->insertGetId([
        'user_id' => 2,
        'title' => 'PROBE notification',
        'message' => 'probe notification',
        'type' => 'info',
        'entity_type' => '',
        'entity_id' => '',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    $unreadBefore = Notification::unreadCount(2);
    $check('notification.unread-baseline', $unreadBefore > 0, 'count=' . $unreadBefore);

    $first = DB::table('notifications')->where('user_id', 2)->whereNull('read_at')->orderBy('id')->first();

    if ($first !== null) {
        $restoredNotifications[] = (int) $first->id;
        Notification::markRead((int) $first->id, 2);
        $check('notification.markRead', Notification::unreadCount(2) === $unreadBefore - 1);
    }

    Notification::markAllRead(2);
    $check('notification.markAllRead', Notification::unreadCount(2) === 0);
}, static function () use (&$restoredNotifications, &$notificationSnapshot): void {
    $snapshotIds = array_map('intval', array_keys($notificationSnapshot));

    // Remove rows created by the probe, then restore the original read state.
    DB::table('notifications')
        ->where('user_id', 2)
        ->whereNotIn('id', $snapshotIds === [] ? [0] : $snapshotIds)
        ->delete();

    foreach ($notificationSnapshot as $id => $readAt) {
        DB::table('notifications')->where('id', (int) $id)->update(['read_at' => $readAt]);
    }

    $restoredNotifications = [];
});

// ---------------------------------------------------------------------------
// Password resets: createForUser -> findValid -> markUsed, then empty the table.
// ---------------------------------------------------------------------------

$guarded(static function () use ($check): void {
    $user = User::find(1);
    $token = PasswordReset::createForUser($user ?? []);
    $row = PasswordReset::findValid($token);
    $check('passwordReset.roundtrip', $row !== null && $row['email'] === 'josefina.reyes@uc.edu.ph');

    if ($row !== null) {
        PasswordReset::markUsed((int) $row['id']);
    }

    $check('passwordReset.markUsed', PasswordReset::findValid($token) === null);
}, static function (): void {
    DB::table('password_resets')->delete();
});

// ---------------------------------------------------------------------------
// Tracking: record (create + increment), then delete the probe row.
// ---------------------------------------------------------------------------

$guarded(static function () use ($check): void {
    Tracking::record('PROBE source', 'Announcement', 'PROBE-SRC-1');
    $row = DB::table('tracking')->where('source_id', 'PROBE-SRC-1')->first();
    $check('tracking.record-create', $row !== null && (int) $row->views === 1);

    Tracking::record('PROBE source', 'Announcement', 'PROBE-SRC-1');
    $row = DB::table('tracking')->where('source_id', 'PROBE-SRC-1')->first();
    $check('tracking.record-update', $row !== null && (int) $row->views === 2, $row === null ? 'missing' : 'views=' . $row->views);
}, static function (): void {
    DB::table('tracking')->where('source_id', 'PROBE-SRC-1')->delete();
});

// ---------------------------------------------------------------------------

$completed = true;
ob_end_flush();
echo PHP_EOL;

if ($failures === []) {
    echo 'ALL WRITE CHECKS PASSED', PHP_EOL;
    exit(0);
}

echo count($failures), ' FAILURES:', PHP_EOL;

foreach ($failures as $failure) {
    echo '  - ', $failure, PHP_EOL;
}

exit(1);
