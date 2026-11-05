<?php
/** @var list<array<string, mixed>> $events */
/** @var array<string, string> $filters */
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
<?php endif; ?>
