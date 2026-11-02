<?php
/** @var list<array<string, mixed>> $announcements */
/** @var list<string> $categories */
/** @var array<string, string> $filters */
?><div class="page-head">
    <div>
        <h2 class="page-head__title"><?= e($pageTitle) ?></h2>
        <p class="page-head__sub"><?= e($pageSubtitle) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--outline" href="<?= url('/student/dashboard') ?>">Back to Dashboard</a>
    </div>
</div>

<form class="filter-bar filter-bar--3" method="GET" action="<?= url('/student/announcements') ?>">
    <div class="field">
        <label for="search">Search notices</label>
        <span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" type="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search announcements"></span>
    </div>
    <div class="field">
        <label for="category">Category</label>
        <select class="input" id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__actions">
        <button class="btn btn--primary" type="submit">Filter</button>
        <a class="btn btn--ghost" href="<?= url('/student/announcements') ?>">Reset</a>
    </div>
</form>

<?php if ($announcements === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No announcements found.</strong><div>Try a different search term or category.</div></div></div>
<?php else: ?>
    <div class="feed-list">
        <?php foreach ($announcements as $announcement): ?>
            <article class="feed-item">
                <div class="feed-item__icon avatar avatar--blue"><?= App\Support\Icons::render('megaphone', 16) ?></div>
                <div class="feed-item__body">
                    <div class="feed-item__eyebrow">
                        <span class="badge badge--info"><?= e($announcement['category']) ?></span>
                        <?php if ($announcement['priority'] !== 'Standard Priority'): ?><span class="badge <?= e(status_class($announcement['priority'])) ?>"><?= e($announcement['priority']) ?></span><?php endif; ?>
                        <span class="feed-item__date"><?= e(fmt_date($announcement['published_at'])) ?></span>
                    </div>
                    <h3 class="feed-item__title"><a href="<?= url('/student/announcements/' . $announcement['public_id']) ?>"><?= e($announcement['title']) ?></a></h3>
                    <p class="feed-item__text"><?= e($announcement['summary'] ?: App\Support\Html::summary($announcement['body'], 220)) ?></p>
                    <div class="feed-item__meta">
                        <span><?= e($announcement['author']) ?></span>
                        <span><?= e($announcement['audience']) ?></span>
                        <span><?= number_format((int) $announcement['views']) ?> views</span>
                    </div>
                </div>
                <a class="btn btn--outline btn--sm" href="<?= url('/student/announcements/' . $announcement['public_id']) ?>">Read</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
