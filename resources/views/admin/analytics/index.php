<?php
/** @var list<array<string, mixed>> $students */
/** @var array{total:int,compliant:int,in_progress:int,at_risk:int,rate:float,hours:int,events:int} $summary */
/** @var int $mandatoryTotal */
/** @var list<string> $programs */
/** @var array<string, string> $filters */
/** @var int $bulkNotifyCount */
$bulkNotifyMessage = 'Hello, this is a reminder to continue your mandatory orientation modules. Please sign in to your student portal and check your current progress. If you have already completed all modules, please disregard this reminder. Contact OSAS if you need assistance.';
$tab = $filters['tab'] ?? 'all';
$query = array_filter([
    'search' => $filters['search'] ?? '',
    'program' => $filters['program'] ?? '',
], static fn (string $value): bool => $value !== '');
$tabUrl = static fn (string $key): string => url('/admin/analytics?' . http_build_query(array_merge($query, ['tab' => $key])));
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">Viewing &amp; Participation Tracking Core</h2>
        <p class="page-head__sub">Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.</p>
    </div>
</div>

<div class="stats">
    <div class="stat">
        <div class="stat__label">Cohort Orientation Rate</div>
        <div class="stat__value"><?= number_format($summary['rate'], 0) ?>%</div>
        <div class="stat__sub"><?= number_format($mandatoryTotal) ?> mandatory modules &times; <?= number_format($summary['total']) ?> students</div>
    </div>
    <div class="stat">
        <div class="stat__label">Fully Compliant</div>
        <div class="stat__value"><?= number_format($summary['compliant']) ?></div>
        <div class="stat__sub"><span class="badge badge--verified">Cleared</span> all mandatory modules done</div>
    </div>
    <div class="stat">
        <div class="stat__label">In Progress</div>
        <div class="stat__value"><?= number_format($summary['in_progress']) ?></div>
        <div class="stat__sub"><span class="badge badge--pending">Ongoing</span> <?= number_format($summary['at_risk']) ?> at risk / not started</div>
    </div>
    <div class="stat">
        <div class="stat__label">Co-curricular Engagement</div>
        <div class="stat__value"><?= number_format($summary['hours']) ?> hrs</div>
        <div class="stat__sub">from <?= number_format($summary['events']) ?> events attended</div>
    </div>
</div>

<section class="card panel-card analytics-ledger">
    <div class="analytics-ledger__head">
        <div>
            <div class="panel-title">Student Participation &amp; Orientation Ledger</div>
            <p class="panel-card__sub">Monitor portal content reach, engagement quality, and viewing activity across all broadcasting modules.</p>
        </div>
        <form class="analytics-ledger__filters" method="GET" action="<?= url('/admin/analytics') ?>">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search student ID, name, program..."></span>
            <select class="input" name="program">
                <option value="">All programs</option>
                <?php foreach ($programs as $program): ?><option value="<?= e($program) ?>"<?= ($filters['program'] ?? '') === $program ? ' selected' : '' ?>><?= e($program) ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="tabs analytics-tabs">
        <a class="tab<?= $tab === 'all' ? ' tab--on' : '' ?>" href="<?= $tabUrl('all') ?>">All Student Logs (<?= number_format($summary['total']) ?>)</a>
        <a class="tab<?= $tab === 'progress' ? ' tab--on' : '' ?>" href="<?= $tabUrl('progress') ?>">Orientation Progress (<?= number_format($summary['in_progress']) ?>)</a>
        <a class="tab<?= $tab === 'at-risk' ? ' tab--on' : '' ?>" href="<?= $tabUrl('at-risk') ?>">At-Risk / Incomplete (<?= number_format($summary['at_risk']) ?>)</a>
    </div>
</section>

<div class="panel-title analytics-section-title">1. Student Participation Ledger</div>

<div class="table-wrap">
    <?php if ($bulkNotifyCount > 0): ?>
        <div class="table-toolbar analytics-table-toolbar">
            <div></div>
            <button class="btn btn--outline btn--sm" type="button" data-notify-open data-notify-url="<?= e(url('/admin/analytics/notify')) ?>" data-notify-name="<?= e(number_format($bulkNotifyCount) . ' non-compliant students') ?>" data-notify-audience="in their student portals" data-notify-message="<?= e($bulkNotifyMessage) ?>" data-notify-return="list">Bulk Notify (<?= number_format($bulkNotifyCount) ?>)</button>
        </div>
    <?php endif; ?>
    <?php if ($students === []): ?>
        <div class="empty-state"><strong>No students match the current filters.</strong></div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data">
                <thead>
                    <tr><th>Student ID &amp; Name</th><th>Program &amp; College</th><th>Orientation Completed</th><th>Attended Events</th><th>Status</th><th class="u-right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $status = (string) $student['tracking_status'];
                        $statusBadge = match ($status) {
                            'Compliant' => 'badge--verified',
                            'In Progress' => 'badge--pending',
                            default => 'badge--high',
                        };
                        $studentName = trim($student['first_name'] . ' ' . $student['last_name']);
                        $notifyMessage = sprintf(
                            'Hello %s, you have completed %d of %d mandatory orientation modules (%s%%). Please sign in to your student portal and continue with your remaining modules. If you have already completed them, please disregard this reminder. Contact OSAS if you need assistance.',
                            $studentName,
                            (int) $student['orientation_completed'],
                            (int) $student['orientation_total'],
                            number_format((float) $student['orientation_percent'], 0),
                        );
                        ?>
                        <tr>
                            <td>
                                <div class="cell-title"><?= e($student['student_id'] ?: 'Not assigned') ?></div>
                                <div class="cell-title"><?= e(trim($student['first_name'] . ' ' . $student['last_name'])) ?></div>
                                <div class="cell-sub"><?= e($student['email']) ?></div>
                            </td>
                            <td>
                                <div class="cell-title"><?= e($student['program'] ?: 'Not assigned') ?></div>
                                <div class="cell-sub"><?= e($student['department'] ?: 'No college assigned') ?></div>
                            </td>
                            <td>
                                <div class="cell-title"><?= number_format((float) $student['orientation_percent'], 0) ?>% Modules (<?= number_format((float) $student['orientation_percent'], 0) ?>%)</div>
                                <div class="progress-bar"><span style="width: <?= min(100, (float) $student['orientation_percent']) ?>%"></span></div>
                                <div class="cell-sub"><?= number_format((int) $student['orientation_completed']) ?> of <?= number_format((int) $student['orientation_total']) ?> modules</div>
                            </td>
                            <td>
                                <div class="cell-title"><?= number_format((int) $student['events_attended']) ?> Events Logged</div>
                                <div class="cell-sub"><?= number_format((int) $student['credit_hours']) ?> Co-Curricular Hrs</div>
                            </td>
                            <td><span class="badge <?= e($statusBadge) ?>"><?= e($status) ?></span></td>
                            <td class="u-right">
                                <div class="cell-actions">
                                    <a class="btn btn--primary btn--sm" href="<?= url('/admin/analytics/' . $student['public_id']) ?>"><?= App\Support\Icons::render('eye', 13) ?> View</a>
                                    <?php if ($status !== 'Compliant'): ?>
                                        <button class="btn btn--outline btn--sm" type="button" data-notify-open data-notify-url="<?= e(url('/admin/analytics/' . $student['public_id'] . '/notify')) ?>" data-notify-name="<?= e($studentName) ?>" data-notify-message="<?= e($notifyMessage) ?>" data-notify-return="list">Notify</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<?= App\Support\View::render('admin/analytics/notify-modal') ?>
