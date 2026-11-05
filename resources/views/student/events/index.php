<?php
/** @var list<array<string, mixed>> $events */
/** @var array<string, string> $filters */
<<<<<<< HEAD
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
</div>

<form class="filter-bar ev-search" method="GET" action="<?= url('/student/events') ?>">
    <div class="field">
        <span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search event, venue or topic..."></span>
    </div>
</form>

<?php if ($events === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No events found.</strong></div></div>
<?php else: ?>
    <div class="ev-list">
        <?php foreach ($events as $event): ?>
            <?php $start = strtotime((string) $event['start_at']); ?>
            <article class="ev-card">
                <div class="ev-card__date">
                    <span><?= e(date('M', $start)) ?></span>
                    <strong><?= e(date('d', $start)) ?></strong>
                    <em><?= e(date('Y', $start)) ?></em>
                </div>
                <div class="ev-card__body">
                    <div class="ev-card__badges">
                        <span class="badge <?= e(status_class($event['status'])) ?>"><?= e($event['status']) ?></span>
                        <span class="badge badge--info"><?= e($event['category']) ?></span>
                    </div>
                    <h3 class="ev-card__title"><a href="<?= url('/student/events/' . $event['public_id']) ?>"><?= e($event['title']) ?></a></h3>
                    <p class="ev-card__desc"><?= e(App\Support\Html::summary($event['description'], 200)) ?></p>
                    <div class="ev-card__meta">
                        <span><?= App\Support\Icons::render('clock', 12) ?> <?= e(date('g:i A', $start)) ?></span>
                        <span><?= App\Support\Icons::render('pin', 12) ?> <?= e($event['venue']) ?></span>
                        <span><?= e($event['mode']) ?></span>
                    </div>
                </div>
                <a class="btn btn--outline btn--sm ev-card__action" href="<?= url('/student/events/' . $event['public_id']) ?>">View Details</a>
            </article>
        <?php endforeach; ?>
    </div>
=======
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--outline" href="<?= url('/student/dashboard') ?>">Back to Dashboard</a></div>
<form class="filter-bar filter-bar--2" method="GET" action="<?= url('/student/events') ?>"><div class="field"><label for="search">Search events</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Event, venue or topic"></span></div><div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Search</button><a class="btn btn--ghost" href="<?= url('/student/events') ?>">Reset</a></div></form>
<?php if ($events === []): ?><div class="card panel-card"><div class="empty-state"><strong>No events found.</strong></div></div><?php else: ?>
<div class="event-grid">
    <?php foreach ($events as $event): ?>
        <article class="card event-card">
            <div class="event-card__date"><span><?= e(date('M', strtotime((string) $event['start_at']))) ?></span><strong><?= e(date('d', strtotime((string) $event['start_at']))) ?></strong><em><?= e(date('Y', strtotime((string) $event['start_at']))) ?></em></div>
            <div class="event-card__body">
                <div class="feed-item__eyebrow"><span class="badge <?= e(status_class($event['status'])) ?>"><?= e($event['status']) ?></span><span class="badge badge--info"><?= e($event['category']) ?></span></div>
                <h3 class="feed-item__title"><a href="<?= url('/student/events/' . $event['public_id']) ?>"><?= e($event['title']) ?></a></h3>
                <p class="feed-item__text"><?= e(App\Support\Html::summary($event['description'], 180)) ?></p>
                <div class="feed-item__meta"><span><?= App\Support\Icons::render('clock', 12) ?> <?= e(date('g:i A', strtotime((string) $event['start_at']))) ?></span><span><?= App\Support\Icons::render('pin', 12) ?> <?= e($event['venue']) ?></span><span><?= e($event['mode']) ?></span></div>
                <a class="btn btn--outline btn--sm" href="<?= url('/student/events/' . $event['public_id']) ?>">View Details</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
<?php endif; ?>
