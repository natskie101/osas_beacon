<?php
/** @var list<array<string, mixed>> $tickets */
/** @var array<string, int> $counts */
?><div class="page-head">
    <div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <a class="btn btn--primary" href="<?= url('/student/support/create') ?>"><?= App\Support\Icons::render('plus', 14) ?> Submit a Concern</a>
</div>

<div class="stats">
    <div class="stat"><div class="stat__label">My Tickets</div><div class="stat__value"><?= number_format($counts['total']) ?></div><div class="stat__sub">All concerns submitted</div></div>
    <div class="stat"><div class="stat__label">Open</div><div class="stat__value"><?= number_format($counts['open']) ?></div><div class="stat__sub">Waiting for OSAS</div></div>
    <div class="stat"><div class="stat__label">In Progress</div><div class="stat__value"><?= number_format($counts['in_progress']) ?></div><div class="stat__sub">Being handled</div></div>
    <div class="stat"><div class="stat__label">Resolved</div><div class="stat__value"><?= number_format($counts['resolved']) ?></div><div class="stat__sub">Answered concerns</div></div>
</div>

<?php if ($tickets === []): ?>
    <div class="card panel-card">
        <div class="empty-state">
            <strong>You have not submitted any concerns yet.</strong>
            <div>Use the support desk whenever you need help from OSAS.</div>
            <a class="btn btn--primary" href="<?= url('/student/support/create') ?>">Submit a Concern</a>
        </div>
    </div>
<?php else: ?>
    <div class="feed-list">
        <?php foreach ($tickets as $ticket): ?>
            <article class="feed-item">
                <div class="feed-item__icon avatar avatar--blue"><?= App\Support\Icons::render('headset', 16) ?></div>
                <div class="feed-item__body">
                    <div class="feed-item__eyebrow">
                        <span class="badge <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span>
                        <span class="badge badge--info"><?= e($ticket['category']) ?></span>
                        <span class="feed-item__date"><?= e(time_ago($ticket['submitted_at'])) ?></span>
                    </div>
                    <h3 class="feed-item__title"><a href="<?= url('/student/support/' . $ticket['public_id']) ?>"><?= e($ticket['subject']) ?></a></h3>
                    <p class="feed-item__text"><?= e(App\Support\Html::summary($ticket['description'], 200)) ?></p>
                    <div class="feed-item__meta">
                        <span><?= e($ticket['public_id']) ?></span>
                        <span><?= e($ticket['priority']) ?> priority</span>
                        <span>Assigned to <?= e($ticket['assigned_to'] ?: 'OSAS Support') ?></span>
                    </div>
                </div>
                <a class="btn btn--outline btn--sm" href="<?= url('/student/support/' . $ticket['public_id']) ?>">Open</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
