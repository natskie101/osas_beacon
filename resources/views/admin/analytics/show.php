<?php
/** @var array<string, mixed> $student */
/** @var list<array<string, mixed>> $modules */
/** @var list<array<string, mixed>> $attendance */
/** @var list<array<string, mixed>> $quizAttempts */
/** @var int $completed */
/** @var int $total */
/** @var int $hours */
/** @var string $trackingStatus */
$fullName = trim($student['first_name'] . ' ' . $student['last_name']);
$percent = $total === 0 ? 0.0 : round(($completed / $total) * 100, 1);
$statusTone = match ($trackingStatus) {
    'Compliant' => 'badge--verified',
    'In Progress' => 'badge--pending',
    default => 'badge--high',
};
$outstanding = array_values(array_filter($modules, static fn (array $module): bool => $module['status'] !== 'Completed'));
?>
<div class="page-head">
    <div>
        <div class="page-head__eyebrow"><a href="<?= url('/admin/analytics') ?>">Viewing &amp; Tracking</a> / <?= e($student['public_id']) ?></div>
        <h2 class="page-head__title">View Student Profile &amp; Tracking Logs</h2>
        <p class="page-head__sub"><?= e($fullName) ?> &middot; <?= e($student['student_id'] ?: $student['public_id']) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="editor-close" href="<?= url('/admin/analytics') ?>" aria-label="Close">&times;</a>
    </div>
</div>

<section class="card panel-card tracking-profile">
    <div class="tracking-profile__identity">
        <span class="avatar avatar--lg"><?= e(initials($student['first_name'], $student['last_name'])) ?></span>
        <div>
            <div class="tracking-profile__name"><?= e($fullName) ?></div>
            <div class="tracking-profile__meta"><?= e($student['email']) ?> &middot; <?= e($student['program'] ?: 'No program') ?> &middot; Year Level: <?= e($student['year_level'] ?: 'Not set') ?></div>
        </div>
    </div>
    <span class="badge <?= e($statusTone) ?>"><?= e(strtoupper($trackingStatus)) ?></span>
</section>

<section class="card panel-card tracking-section">
    <div class="tracking-section__head">
        <div class="tracking-section__title">Mandatory Orientation Progress (<?= number_format($completed) ?> / <?= number_format($total) ?> Completed)</div>
        <div class="tracking-section__percent"><?= number_format($percent, 0) ?>% Modules (<?= number_format($percent, 0) ?>%)</div>
    </div>
    <div class="progress-bar progress-bar--lg"><span style="width: <?= min(100, $percent) ?>%"></span></div>

    <?php if ($completed > 0): ?>
        <div class="tracking-list">
            <?php foreach ($modules as $module): ?>
                <?php if ($module['status'] === 'Completed'): ?>
                    <div class="tracking-item">
                        <span class="tracking-item__icon tracking-item__icon--green"><?= App\Support\Icons::render('check', 13) ?></span>
                        <div>
                            <div class="tracking-item__title"><?= e($module['title']) ?></div>
                            <div class="tracking-item__meta">Completed: <?= e(fmt_date($module['completed_at'] ?? $module['last_viewed_at'])) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card panel-card tracking-section">
    <div class="tracking-section__head">
        <div class="tracking-section__title">Outstanding Mandatory Modules (<?= number_format(count($outstanding)) ?> Remaining)</div>
    </div>
    <?php if ($outstanding === []): ?>
        <div class="tracking-empty">All mandatory orientation modules are complete.</div>
    <?php else: ?>
        <div class="tracking-list">
            <?php foreach ($outstanding as $module): ?>
                <div class="tracking-item">
                    <span class="tracking-item__icon<?= $module['status'] === 'In Progress' ? ' tracking-item__icon--amber' : '' ?>"><?= App\Support\Icons::render($module['status'] === 'In Progress' ? 'clock' : 'dot', 13) ?></span>
                    <div>
                        <div class="tracking-item__title"><?= e($module['title']) ?></div>
                        <div class="tracking-item__meta"><?= e($module['status'] ?: 'Not Started') ?> &middot; <?= number_format((int) $module['progress']) ?>%</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card panel-card tracking-section">
    <div class="tracking-section__head">
        <div class="tracking-section__title">Quiz Assessments</div>
    </div>
    <?php if ($quizAttempts === []): ?>
        <div class="tracking-empty">No quiz assessments are assigned for this student.</div>
    <?php else: ?>
        <div class="tracking-list">
            <?php foreach ($quizAttempts as $quiz): ?>
                <div class="tracking-item">
                    <span class="tracking-item__icon<?= (int) $quiz['passed'] === 1 ? ' tracking-item__icon--green' : ' tracking-item__icon--amber' ?>"><?= App\Support\Icons::render((int) $quiz['passed'] === 1 ? 'check' : 'clock', 13) ?></span>
                    <div>
                        <div class="tracking-item__title"><?= e($quiz['module_title']) ?></div>
                        <div class="tracking-item__meta"><?= number_format((int) $quiz['attempts']) ?> attempt<?= (int) $quiz['attempts'] === 1 ? '' : 's' ?> &middot; best <?= number_format((float) $quiz['best_score'], 0) ?>% &middot; passing <?= number_format((float) $quiz['passing_rate'], 0) ?>% &middot; <?= (int) $quiz['passed'] === 1 ? 'Passed' : 'Not passed yet' ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card panel-card tracking-section">
    <div class="tracking-section__head">
        <div class="tracking-section__title">Attended Campus Events (<?= number_format(count($attendance)) ?> Events / <?= number_format($hours) ?> Hours)</div>
    </div>
    <?php if ($attendance === []): ?>
        <div class="tracking-empty">No campus events have been logged for this student.</div>
    <?php else: ?>
        <div class="tracking-list">
            <?php foreach ($attendance as $event): ?>
                <div class="tracking-event">
                    <div>
                        <div class="tracking-item__title"><?= e($event['event_name']) ?></div>
                        <div class="tracking-item__meta"><?= e(fmt_date($event['attended_at'])) ?></div>
                    </div>
                    <span class="tracking-event__credit">+<?= number_format((int) $event['credit_hours']) ?> Credit Hrs</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="form-actions">
    <button type="button" class="btn btn--outline" onclick="window.print()">Print Student Summary</button>
    <a class="btn btn--primary" href="<?= url('/admin/analytics') ?>">Close Details</a>
</div>
