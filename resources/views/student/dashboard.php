<?php
/** @var array<string, mixed> $user */
/** @var array{completed:int,in_progress:int,not_started:int,total:int,percent:float} $orientation */
/** @var int $announcementCount */
/** @var list<array<string, mixed>> $announcements */
/** @var list<array<string, mixed>> $events */
/** @var int $ticketCount */
/** @var int $activeTickets */
/** @var int $requestCount */
/** @var int $activeRequests */
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$program = $user['program'] ?: ($user['department'] ?: $user['role']);
$orientationLabel = $orientation['completed'] >= $orientation['total'] && $orientation['total'] > 0
    ? 'Review Orientation'
    : 'Continue Orientation';
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">Welcome back, <?= e($user['first_name'] ?? 'Student') ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <div class="page-head__actions">
        <a href="<?= url('/student/orientation') ?>" class="btn btn--primary"><?= e($orientationLabel) ?></a>
    </div>
</div>

<section class="card panel-card profile-card">
    <div class="profile-card__identity">
        <span class="avatar avatar--lg"><?= e(initials($user['first_name'] ?? '', $user['last_name'] ?? '')) ?></span>
        <div>
            <div class="profile-card__name"><?= e($fullName) ?></div>
            <div class="profile-card__meta"><?= e($program) ?><?= !empty($user['year_level']) ? ' · ' . e($user['year_level']) : '' ?></div>
            <div class="profile-card__meta"><?= e($user['email']) ?></div>
        </div>
    </div>
    <div class="profile-card__facts">
        <div><span>Student ID</span><strong><?= e($user['student_id'] ?: 'Not assigned') ?></strong></div>
        <div><span>Status</span><strong><span class="badge <?= e(status_class($user['status'])) ?>"><?= e($user['status']) ?></span></strong></div>
        <div><span>Last sign-in</span><strong><?= e(fmt_datetime($user['last_login_at'])) ?></strong></div>
    </div>
</section>

<div class="stats">
    <div class="stat">
        <div class="stat__label">Orientation</div>
        <div class="stat__value"><?= number_format($orientation['completed']) ?> / <?= number_format($orientation['total']) ?></div>
        <div class="stat__sub"><?= number_format($orientation['percent'], 1) ?>% complete &middot; <?= number_format($orientation['in_progress']) ?> in progress</div>
    </div>
    <div class="stat">
        <div class="stat__label">Announcements</div>
        <div class="stat__value"><?= number_format($announcementCount) ?></div>
        <div class="stat__sub">Published notices from OSAS</div>
    </div>
    <div class="stat">
        <div class="stat__label">Support Tickets</div>
        <div class="stat__value"><?= number_format($ticketCount) ?></div>
        <div class="stat__sub"><?= number_format($activeTickets) ?> open or in progress</div>
    </div>
    <div class="stat">
        <div class="stat__label">Document Requests</div>
        <div class="stat__value"><?= number_format($requestCount) ?></div>
        <div class="stat__sub"><?= number_format($activeRequests) ?> being processed</div>
    </div>
</div>

<div class="dash-grid">
    <section class="card panel-card">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Latest OSAS Announcements</div>
                <p class="panel-card__sub">Official updates published for your account.</p>
            </div>
            <a href="<?= url('/student/announcements') ?>" class="link">View all</a>
        </div>
        <?php if ($announcements === []): ?>
            <div class="empty-state">No announcements have been published yet.</div>
        <?php else: ?>
            <div class="log-list log-list--card">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="log-item">
                        <span class="log-item__ico log-item__ico--blue"><?= App\Support\Icons::render('megaphone', 13) ?></span>
                        <div class="log-item__body">
                            <div class="log-item__title">
                                <a class="link" href="<?= url('/student/announcements/' . $announcement['public_id']) ?>"><?= e($announcement['title']) ?></a>
                            </div>
                            <div class="log-item__text"><?= e($announcement['summary'] ?: 'Open the announcement to read the full details.') ?></div>
                            <div class="log-item__time">
                                <?= App\Support\Icons::render('clock', 11) ?>
                                <?= e(time_ago($announcement['published_at'] ?: $announcement['created_at'])) ?> &middot; <?= e(fmt_date($announcement['published_at'] ?: $announcement['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card panel-card">
        <div class="panel-card__head">
            <div>
                <div class="panel-title">Upcoming Events</div>
                <p class="panel-card__sub">Activities scheduled by OSAS.</p>
            </div>
            <a href="<?= url('/student/events') ?>" class="link">View all</a>
        </div>
        <?php if ($events === []): ?>
            <div class="empty-state">No upcoming events are scheduled.</div>
        <?php else: ?>
            <div class="log-list log-list--card">
                <?php foreach ($events as $event): ?>
                    <div class="log-item">
                        <span class="log-item__ico log-item__ico--green"><?= App\Support\Icons::render('calendar', 13) ?></span>
                        <div class="log-item__body">
                            <div class="log-item__title">
                                <a class="link" href="<?= url('/student/events/' . $event['public_id']) ?>"><?= e($event['title']) ?></a>
                            </div>
                            <div class="log-item__text"><?= e($event['venue']) ?> &middot; <?= e($event['mode']) ?></div>
                            <div class="log-item__time">
                                <?= App\Support\Icons::render('clock', 11) ?>
                                <?= e(fmt_datetime($event['start_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
