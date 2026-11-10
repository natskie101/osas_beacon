<?php

declare(strict_types=1);

/**
 * Golden-output snapshot of every read-only model method.
 *
 * Usage:
 *   php scripts/model-snapshot.php scripts/model-snapshot-before.json
 *
 * Run before and after a refactor batch, then compare the two files byte for
 * byte. Writes are intentionally excluded (they mutate the database); those
 * paths are covered by the HTTP check suite instead.
 *
 * Normalisation: the legacy App\Support\DB wrapper fetched rows as PDO
 * FETCH_BOTH, so every row carried duplicate integer keys ("id" => 1, "0" => 1).
 * Those artifacts are stripped here, and associative arrays are key-sorted, so
 * the snapshots stay comparable across the query-builder conversion.
 */

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Broadcast;
use App\Models\DocumentRequest;
use App\Models\Event;
use App\Models\File;
use App\Models\LostFoundClaim;
use App\Models\LostFoundItem;
use App\Models\Notification;
use App\Models\OrientationModule;
use App\Models\OrientationProgress;
use App\Models\Participation;
use App\Models\PasswordReset;
use App\Models\Regulation;
use App\Models\RememberToken;
use App\Models\SupportTicket;
use App\Models\Tracking;
use App\Models\User;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** Recursively drop PDO FETCH_BOTH integer artifacts and stabilise key order. */
function normalize_tree(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }

    $hasString = false;
    $hasInt = false;

    foreach ($value as $key => $_) {
        if (is_int($key)) {
            $hasInt = true;
        } else {
            $hasString = true;
        }
    }

    $out = [];

    foreach ($value as $key => $item) {
        if ($hasString && $hasInt && is_int($key)) {
            continue;
        }

        $out[$key] = normalize_tree($item);
    }

    $allIntKeys = true;

    foreach (array_keys($out) as $key) {
        if (!is_int($key)) {
            $allIntKeys = false;
            break;
        }
    }

    if (!$allIntKeys) {
        ksort($out);
    }

    return $out;
}

$snapshot = [];

$add = static function (string $key, callable $fn) use (&$snapshot): void {
    try {
        $snapshot[$key] = $fn();
    } catch (Throwable $exception) {
        $snapshot[$key] = 'EXCEPTION ' . get_class($exception) . ': ' . $exception->getMessage();
    }
};

// ---------------------------------------------------------------------------
// BaseModel generics, exercised across every subclass table.
// ---------------------------------------------------------------------------

foreach ([
    'Announcement' => 'ANN-2026-001',
    'Broadcast' => 'NOTIF-2026-001',
    'DocumentRequest' => 'DR-2026-001',
    'Event' => 'EVT-2026-001',
    'LostFoundClaim' => 'CLM-2026-001',
    'LostFoundItem' => 'LF-2026-001',
    'OrientationModule' => 'ORIENT-2026-001',
    'Regulation' => 'REG-2026-001',
    'Report' => 'REP-2026-001',
    'SupportTicket' => 'TCS-2026-001',
    'Tracking' => 'TRK-2026-001',
] as $class => $publicId) {
    $fqcn = 'App\\Models\\' . $class;

    $add("$class.find", static fn (): mixed => $fqcn::find(1));
    $add("$class.find-miss", static fn (): mixed => $fqcn::find(987654));
    $add("$class.findByPublicId", static fn (): mixed => $fqcn::findByPublicId($publicId));
    $add("$class.all", static fn (): mixed => $fqcn::all());
    $add("$class.all-oldest", static fn (): mixed => $fqcn::all('created_at ASC'));
    $add("$class.count", static fn (): mixed => $fqcn::count());
    $add("$class.count-where", static fn (): mixed => $fqcn::count('public_id = ?', [$publicId]));
    $add("$class.paginate", static fn (): mixed => $fqcn::paginate([], 1));
    $add("$class.paginate-search", static fn (): mixed => $fqcn::paginate(['search' => 'a'], 1));
    $add("$class.paginate-oldest", static fn (): mixed => $fqcn::paginate([], 1, 12, [], 'oldest'));
}

// Extra order/column-filter paths on tables that have those columns.
$add('Announcement.paginate-date', static fn (): mixed => Announcement::paginate([], 1, 12, [], 'date'));
$add('Announcement.paginate-title', static fn (): mixed => Announcement::paginate([], 1, 12, [], 'title'));
$add('Announcement.paginate-views', static fn (): mixed => Announcement::paginate([], 1, 12, [], 'views'));
$add('Announcement.paginate-status', static fn (): mixed => Announcement::paginate(['status' => 'Published'], 1, 12, ['Published', 'Draft']));
$add('Announcement.paginate-full', static fn (): mixed => Announcement::paginate(
    ['search' => 'welcome', 'status' => 'Published', 'category' => 'Academic'],
    1,
    12,
    ['Published', 'Draft', 'Scheduled'],
    'date',
    ['category' => 'category'],
));
$add('LostFoundItem.paginate-column-filter', static fn (): mixed => LostFoundItem::paginate(
    ['category' => 'Lost'],
    1,
    12,
    [],
    'newest',
    ['category' => 'category'],
));
$add('LostFoundItem.paginate-status', static fn (): mixed => LostFoundItem::paginate(
    ['status' => 'Open'],
    1,
    12,
    ['Open', 'Unclaimed', 'Claim Pending', 'Returned', 'Archived'],
));

// ---------------------------------------------------------------------------
// User
// ---------------------------------------------------------------------------

$add('User.find', static fn (): mixed => User::find(1));
$add('User.find-9', static fn (): mixed => User::find(9));
$add('User.find-miss', static fn (): mixed => User::find(987654));
$add('User.findByEmail', static fn (): mixed => User::findByEmail('  JOSEFINA.REYES@UC.EDU.PH  '));
$add('User.findByEmail-miss', static fn (): mixed => User::findByEmail('nobody@example.com'));
$add('User.findByPublicId', static fn (): mixed => User::findByPublicId('USR-1002'));
$add('User.all', static fn (): mixed => User::all());
$add('User.byRole-Student', static fn (): mixed => User::byRole('Student'));
$add('User.byRole-Administrator', static fn (): mixed => User::byRole('Administrator'));
$add('User.counts', static fn (): mixed => User::counts());
$add('User.paginate-default', static fn (): mixed => User::paginate([], 1));
$add('User.paginate-search-name', static fn (): mixed => User::paginate(['search' => 'rey'], 1));
$add('User.paginate-search-id', static fn (): mixed => User::paginate(['search' => 'USR-100'], 1));
$add('User.paginate-role', static fn (): mixed => User::paginate(['role' => 'Student'], 1));
$add('User.paginate-status', static fn (): mixed => User::paginate(['status' => 'Active'], 1));
$add('User.paginate-group-students', static fn (): mixed => User::paginate(['group' => 'students'], 1));
$add('User.paginate-group-staff', static fn (): mixed => User::paginate(['group' => 'staff'], 1));
$add('User.paginate-exclude-pending', static fn (): mixed => User::paginate(['exclude_pending' => 1], 1));
$add('User.paginate-sort-created', static fn (): mixed => User::paginate(['sort' => 'created', 'direction' => 'desc'], 1, 5));
$add('User.paginate-sort-last-login', static fn (): mixed => User::paginate(['sort' => 'last_login'], 1, 5));
$add('User.paginate-sort-status', static fn (): mixed => User::paginate(['sort' => 'status', 'direction' => 'desc'], 1, 5));
$add('User.paginate-sort-role', static fn (): mixed => User::paginate(['sort' => 'role'], 1, 5));
$add('User.paginate-page-clamp', static fn (): mixed => User::paginate([], 99, 5));
$add('User.paginate-empty', static fn (): mixed => User::paginate(['search' => 'zzz-no-match'], 1));
$add('User.fullName', static fn (): mixed => User::fullName(['first_name' => 'Josefina', 'last_name' => 'Reyes']));

// ---------------------------------------------------------------------------
// Announcement
// ---------------------------------------------------------------------------

$add('Announcement.counts', static fn (): mixed => Announcement::counts());
$add('Announcement.published', static fn (): mixed => Announcement::published());
$add('Announcement.published-limit', static fn (): mixed => Announcement::published(3));
$add('Announcement.publishedCount', static fn (): mixed => Announcement::publishedCount());
$add('Announcement.findPublished', static fn (): mixed => Announcement::findPublished('ANN-2026-001'));

// ---------------------------------------------------------------------------
// Broadcast
// ---------------------------------------------------------------------------

$add('Broadcast.counts', static fn (): mixed => Broadcast::counts());
$add('Broadcast.forStudent-null', static fn (): mixed => Broadcast::forStudent(null));
$add('Broadcast.forStudent-Student', static fn (): mixed => Broadcast::forStudent('Student'));
$add('Broadcast.forStudent-empty', static fn (): mixed => Broadcast::forStudent(''));

// ---------------------------------------------------------------------------
// DocumentRequest
// ---------------------------------------------------------------------------

$add('DocumentRequest.counts', static fn (): mixed => DocumentRequest::counts());
$add('DocumentRequest.forStudent', static fn (): mixed => DocumentRequest::forStudent(2));
$add('DocumentRequest.findForStudent', static fn (): mixed => DocumentRequest::findForStudent('DR-2026-001', 2));
$add('DocumentRequest.findForStudent-wrong-user', static fn (): mixed => DocumentRequest::findForStudent('DR-2026-001', 1));
$add('DocumentRequest.timeline', static fn (): mixed => DocumentRequest::timeline(1));
$add('DocumentRequest.statuses', static fn (): mixed => DocumentRequest::statuses());
$add('DocumentRequest.filterOptions', static fn (): mixed => DocumentRequest::filterOptions());

// ---------------------------------------------------------------------------
// Event
// ---------------------------------------------------------------------------

$add('Event.counts', static fn (): mixed => Event::counts());
$add('Event.upcoming', static fn (): mixed => Event::upcoming());
$add('Event.upcoming-limit', static fn (): mixed => Event::upcoming(3));
$add('Event.upcomingCount', static fn (): mixed => Event::upcomingCount());
$add('Event.forStudent', static fn (): mixed => Event::forStudent());
$add('Event.findVisible', static fn (): mixed => Event::findVisible('EVT-2026-001'));

// ---------------------------------------------------------------------------
// LostFoundItem
// ---------------------------------------------------------------------------

$add('LostFoundItem.counts', static fn (): mixed => LostFoundItem::counts());
$add('LostFoundItem.pendingClaimCounts', static fn (): mixed => LostFoundItem::pendingClaimCounts());
$add('LostFoundItem.visible', static fn (): mixed => LostFoundItem::visible());
$add('LostFoundItem.findVisible', static fn (): mixed => LostFoundItem::findVisible('LF-2026-001'));
$add('LostFoundItem.claimsFor', static fn (): mixed => LostFoundItem::claimsFor(1));
$add('LostFoundItem.forReporter', static fn (): mixed => LostFoundItem::forReporter('ryan.delacruz@uc.edu.ph'));
$add('LostFoundItem.forReporter-student-id', static fn (): mixed => LostFoundItem::forReporter('x@y.z', '2026-0001'));
$add('LostFoundItem.claimsByUser', static fn (): mixed => LostFoundItem::claimsByUser(2));
$add('LostFoundItem.pendingClaims', static fn (): mixed => LostFoundItem::pendingClaims());
$add('LostFoundItem.pendingClaims-limit', static fn (): mixed => LostFoundItem::pendingClaims(3));

// ---------------------------------------------------------------------------
// OrientationModule
// ---------------------------------------------------------------------------

$add('OrientationModule.counts', static fn (): mixed => OrientationModule::counts());
$add('OrientationModule.published', static fn (): mixed => OrientationModule::published());
$add('OrientationModule.findPublished', static fn (): mixed => OrientationModule::findPublished('ORIENT-2026-001'));
$add('OrientationModule.categories', static fn (): mixed => OrientationModule::categories());

// ---------------------------------------------------------------------------
// Regulation
// ---------------------------------------------------------------------------

$add('Regulation.counts', static fn (): mixed => Regulation::counts());
$add('Regulation.published', static fn (): mixed => Regulation::published());
$add('Regulation.findPublished', static fn (): mixed => Regulation::findPublished('REG-2026-001'));
$add('Regulation.categories', static fn (): mixed => Regulation::categories());
$add('Regulation.publishedCategoryCounts', static fn (): mixed => Regulation::publishedCategoryCounts());

// ---------------------------------------------------------------------------
// SupportTicket
// ---------------------------------------------------------------------------

$add('SupportTicket.counts', static fn (): mixed => SupportTicket::counts());
$add('SupportTicket.forStudent', static fn (): mixed => SupportTicket::forStudent(2));
$add('SupportTicket.findForStudent', static fn (): mixed => SupportTicket::findForStudent('TCS-2026-001', 2));
$add('SupportTicket.findForStudent-wrong-user', static fn (): mixed => SupportTicket::findForStudent('TCS-2026-001', 1));
$add('SupportTicket.imageAttachment', static fn (): mixed => SupportTicket::imageAttachment('TCS-2026-001'));
$add('SupportTicket.imageAttachment-miss', static fn (): mixed => SupportTicket::imageAttachment('TCS-9999-999'));
$add('SupportTicket.messages', static fn (): mixed => SupportTicket::messages(400));

// ---------------------------------------------------------------------------
// Tracking
// ---------------------------------------------------------------------------

$add('Tracking.totals', static fn (): mixed => Tracking::totals());

// ---------------------------------------------------------------------------
// AuditLog
// ---------------------------------------------------------------------------

$add('AuditLog.recent', static fn (): mixed => AuditLog::recent());
$add('AuditLog.recent-limit', static fn (): mixed => AuditLog::recent(3));
$add('AuditLog.recent-clamped', static fn (): mixed => AuditLog::recent(9999));
$add('AuditLog.forUser', static fn (): mixed => AuditLog::forUser(1));
$add('AuditLog.forUser-miss', static fn (): mixed => AuditLog::forUser(987654));
$add('AuditLog.count', static fn (): mixed => AuditLog::count());
$add('AuditLog.paginate', static fn (): mixed => AuditLog::paginate([], 1));
$add('AuditLog.paginate-search', static fn (): mixed => AuditLog::paginate(['search' => 'login'], 1));
$add('AuditLog.paginate-action', static fn (): mixed => AuditLog::paginate(['action' => 'auth.login'], 1));
$add('AuditLog.paginate-combined', static fn (): mixed => AuditLog::paginate(['search' => 'portal', 'action' => 'auth.login'], 1));
$add('AuditLog.paginate-page-clamp', static fn (): mixed => AuditLog::paginate([], 99, 5));
$add('AuditLog.actions', static fn (): mixed => AuditLog::actions());

// ---------------------------------------------------------------------------
// File
// ---------------------------------------------------------------------------

$add('File.find', static fn (): mixed => File::find(2));
$add('File.find-miss', static fn (): mixed => File::find(987654));
$add('File.findForEntity', static fn (): mixed => File::findForEntity(2, 'orientation_media', 'ORIENT-2026-001'));
$add('File.findForEntity-wrong-entity', static fn (): mixed => File::findForEntity(2, 'user_profile_photo', '999'));
$add('File.latestForEntity', static fn (): mixed => File::latestForEntity('support_ticket', 'TCS-2026-001'));
$add('File.latestForEntity-miss', static fn (): mixed => File::latestForEntity('nope', 'x'));

// ---------------------------------------------------------------------------
// Notification
// ---------------------------------------------------------------------------

$add('Notification.forUser', static fn (): mixed => Notification::forUser(2));
$add('Notification.forUser-limit', static fn (): mixed => Notification::forUser(2, 2));
$add('Notification.forUser-miss', static fn (): mixed => Notification::forUser(987654));
$add('Notification.unreadCount', static fn (): mixed => Notification::unreadCount(2));
$add('Notification.unreadCount-none', static fn (): mixed => Notification::unreadCount(1));

// ---------------------------------------------------------------------------
// OrientationProgress
// ---------------------------------------------------------------------------

$add('OrientationProgress.forUser', static fn (): mixed => OrientationProgress::forUser(2));
$add('OrientationProgress.forUser-miss', static fn (): mixed => OrientationProgress::forUser(987654));
$add('OrientationProgress.find', static fn (): mixed => OrientationProgress::find(2, 512));
$add('OrientationProgress.find-miss', static fn (): mixed => OrientationProgress::find(1, 512));
$add('OrientationProgress.summary', static fn (): mixed => OrientationProgress::summary(2, 1));
$add('OrientationProgress.summary-zero', static fn (): mixed => OrientationProgress::summary(987654, 5));
$add('OrientationProgress.summary-no-modules', static fn (): mixed => OrientationProgress::summary(2, 0));

// ---------------------------------------------------------------------------
// Participation
// ---------------------------------------------------------------------------

$add('Participation.mandatoryModuleCount', static fn (): mixed => Participation::mandatoryModuleCount());
$students = Participation::students();
$add('Participation.students', static fn (): mixed => $students);
$add('Participation.summary', static fn (): mixed => Participation::summary($students));
$add('Participation.findStudent', static fn (): mixed => Participation::findStudent('USR-1002'));
$add('Participation.findStudent-non-student', static fn (): mixed => Participation::findStudent('USR-1001'));
$add('Participation.mandatoryModulesFor', static fn (): mixed => Participation::mandatoryModulesFor(2));
$add('Participation.mandatoryModulesFor-miss', static fn (): mixed => Participation::mandatoryModulesFor(987654));
$add('Participation.attendanceFor', static fn (): mixed => Participation::attendanceFor(2));
$add('Participation.quizAttemptsFor', static fn (): mixed => Participation::quizAttemptsFor(2));

// ---------------------------------------------------------------------------
// PasswordReset / RememberToken (read paths only)
// ---------------------------------------------------------------------------

$add('PasswordReset.findValid-miss', static fn (): mixed => PasswordReset::findValid('deadbeefdeadbeefdeadbeefdeadbeef'));
$add('RememberToken.validate-null', static fn (): mixed => RememberToken::validate(null));
$add('RememberToken.validate-no-colon', static fn (): mixed => RememberToken::validate('malformed'));
$add('RememberToken.validate-unknown-selector', static fn (): mixed => RememberToken::validate('x:y'));

// ---------------------------------------------------------------------------
// Protected/private read paths (invoked via reflection; no writes occur).
// ---------------------------------------------------------------------------

$invoke = static function (string $class, string $method, mixed ...$args): mixed {
    $reflection = new ReflectionMethod($class, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke(null, ...$args);
};

foreach ([
    'Announcement' => 'ANN-2026-001',
    'LostFoundClaim' => 'CLM-2026-001',
    'SupportTicket' => 'TCS-2026-001',
    'Tracking' => 'TRK-2026-001',
] as $class => $existingPublicId) {
    $add("$class.nextPublicId", static fn (): mixed => $invoke('App\\Models\\' . $class, 'nextPublicId'));
}

$add('User.uniqueUsername-taken', static fn (): mixed => $invoke(User::class, 'uniqueUsername', 'technical.admin'));
$add('User.uniqueUsername-free', static fn (): mixed => $invoke(User::class, 'uniqueUsername', 'nobody-here'));
$add('User.uniqueUsername-ignore-self', static fn (): mixed => $invoke(User::class, 'uniqueUsername', 'technical.admin', 9));
$add('User.uniqueUsername-suffix', static fn (): mixed => $invoke(User::class, 'uniqueUsername', 'ryan.delacruz'));

// ---------------------------------------------------------------------------
// Pure helpers
// ---------------------------------------------------------------------------

$add('decodeList.json', static fn (): mixed => Announcement::decodeList('["a","b"]'));
$add('decodeList.empty', static fn (): mixed => Announcement::decodeList(''));
$add('decodeList.null', static fn (): mixed => Announcement::decodeList(null));
$add('decodeList.array', static fn (): mixed => Announcement::decodeList([1, 2]));
$add('decodeList.invalid', static fn (): mixed => Announcement::decodeList('not-json'));

// ---------------------------------------------------------------------------

$out = $argv[1] ?? ($root . '/scripts/model-snapshot.json');
$json = json_encode(
    normalize_tree($snapshot),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
);

if ($json === false) {
    fwrite(STDERR, 'json_encode failed: ' . json_last_error_msg() . PHP_EOL);
    exit(1);
}

file_put_contents($out, $json . "\n");
echo 'Wrote ', $out, ' (', count($snapshot), ' entries)', PHP_EOL;
