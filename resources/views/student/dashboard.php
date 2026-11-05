<?php
/** @var array<string, mixed> $user */
<<<<<<< HEAD
/** @var list<array<string, mixed>> $announcements */
/** @var array<string, mixed>|null $featuredModule */
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$program = $user['program'] ?: ($user['department'] ?: $user['role']);
$latest = $announcements[0] ?? null;
?>
<div class="sd-dashboard">
    <section class="sd-hero">
        <div class="sd-hero__identity">
            <div class="sd-hero__eyebrow">Welcome back</div>
            <div class="sd-hero__name"><?= e($fullName) ?></div>
            <div class="sd-hero__program"><?= e($program) ?><?= !empty($user['year_level']) ? ' &middot; ' . e($user['year_level']) : '' ?></div>
        </div>
        <div class="sd-hero__facts">
            <div>
                <span>Student ID</span>
                <strong><?= e($user['student_id'] ?: 'Not assigned') ?></strong>
            </div>
            <div>
                <span>Status</span>
                <strong class="sd-status"><?= e(strtoupper($user['status'])) ?></strong>
            </div>
        </div>
    </section>

    <div class="sd-grid">
        <div class="sd-main">
            <section class="sd-card sd-announcement">
                <div class="sd-label">Announcement</div>
                <?php if ($latest !== null): ?>
                    <h3><a href="<?= url('/student/announcements/' . $latest['public_id']) ?>"><?= e($latest['title']) ?></a></h3>
                    <p><?= e($latest['summary'] ?: 'Open the announcement to read the full details.') ?></p>
                <?php else: ?>
                    <h3>No announcements yet</h3>
                    <p>Official OSAS updates will appear here once published.</p>
                <?php endif; ?>
            </section>

            <section class="sd-poster">
                <img src="<?= asset('img/choose-health-poster.svg') ?>" alt="Choose health, not smoke" loading="lazy">
            </section>
        </div>

        <aside class="sd-side">
            <section class="sd-card sd-side-card">
                <div class="sd-label">Orientation</div>
                <?php if ($featuredModule !== null): ?>
                    <h3><?= e($featuredModule['title']) ?></h3>
                    <p>Please read the Orientation Module for the instructions.</p>
                    <a class="sd-link" href="<?= url('/student/orientation/' . $featuredModule['public_id']) ?>">Instructions <span>&rarr;</span></a>
                <?php else: ?>
                    <h3>Orientation modules</h3>
                    <p>Your orientation modules will appear here once published.</p>
                    <a class="sd-link" href="<?= url('/student/orientation') ?>">Open Orientation <span>&rarr;</span></a>
                <?php endif; ?>
            </section>

            <section class="sd-card sd-side-card">
                <div class="sd-label">Submit Feedback/Report</div>
                <p>Please check Feedback/Report module</p>
                <a class="sd-link" href="<?= url('/student/support') ?>">Open Feedback/Report <span>&rarr;</span></a>
            </section>
        </aside>
    </div>
</div>
=======
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
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
