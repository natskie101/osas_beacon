<?php
/** @var list<array<string, mixed>> $regulations */
/** @var list<string> $categories */
/** @var array<string, string> $filters */
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--outline" href="<?= url('/student/dashboard') ?>">Back to Dashboard</a></div>
<form class="filter-bar filter-bar--3" method="GET" action="<?= url('/student/rules') ?>">
    <div class="field"><label for="search">Search policies</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search the student code"></span></div>
    <div class="field"><label for="category">Category</label><select class="input" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Filter</button><a class="btn btn--ghost" href="<?= url('/student/rules') ?>">Reset</a></div>
</form>
<?php if ($regulations === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No policies found.</strong><div>Try another search term or category.</div></div></div>
<?php else: ?>
    <div class="feed-list">
        <?php foreach ($regulations as $regulation): ?>
            <article class="feed-item">
                <div class="feed-item__icon avatar avatar--amber"><?= App\Support\Icons::render('scale', 16) ?></div>
                <div class="feed-item__body">
                    <div class="feed-item__eyebrow"><span class="badge badge--info"><?= e($regulation['category']) ?></span><span class="feed-item__date"><?= e($regulation['reference_code']) ?></span></div>
                    <h3 class="feed-item__title"><a href="<?= url('/student/rules/' . $regulation['public_id']) ?>"><?= e($regulation['title']) ?></a></h3>
                    <p class="feed-item__text"><?= e($regulation['summary'] ?: App\Support\Html::summary($regulation['body'], 220)) ?></p>
                    <div class="feed-item__meta"><span>Effective <?= e($regulation['effective_date']) ?></span><span><?= e($regulation['review_schedule']) ?></span><span><?= number_format((int) $regulation['views']) ?> views</span></div>
                </div>
                <a class="btn btn--outline btn--sm" href="<?= url('/student/rules/' . $regulation['public_id']) ?>">Read</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
