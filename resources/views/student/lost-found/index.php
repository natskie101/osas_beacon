<?php
/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $myReports */
/** @var list<array<string, mixed>> $myClaims */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
<<<<<<< HEAD
$categories = array_values(array_unique(array_filter(array_map(
    static fn (array $item): string => (string) $item['category'],
    $items,
))));
sort($categories);
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">Lost &amp; Found</h2>
        <p class="page-head__sub">Report a lost item, browse found items and file an ownership claim.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--primary" href="<?= url('/student/lost-found/report') ?>"><?= App\Support\Icons::render('plus', 14) ?> Report an Item</a>
    </div>
</div>

<div class="stats lf-stats">
    <div class="stat">
        <div class="stat__label">Open Reports</div>
        <div class="stat__value"><?= number_format($counts['open'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--info">Directory items</span> Lost or waiting for owners</div>
    </div>
    <div class="stat">
        <div class="stat__label">Claim Pending</div>
        <div class="stat__value"><?= number_format($counts['claim_pending'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--pending">Claim to verify</span> Ownership checks at the OSAS office</div>
    </div>
    <div class="stat">
        <div class="stat__label">My Reports</div>
        <div class="stat__value"><?= number_format(count($myReports)) ?></div>
        <div class="stat__sub"><span class="badge badge--draft">Submitted</span> Items you reported</div>
    </div>
    <div class="stat">
        <div class="stat__label">My Claims</div>
        <div class="stat__value"><?= number_format(count($myClaims)) ?></div>
        <div class="stat__sub"><span class="badge badge--pending">Filed</span> Claims you submitted</div>
    </div>
</div>

<form class="filter-bar lf-filters" method="GET" action="<?= url('/student/lost-found') ?>">
    <div class="field">
        <label for="search">Search</label>
        <span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search code, item, location..."></span>
    </div>
    <div class="field">
        <label for="type">Report type</label>
        <select class="input" id="type" name="type">
            <option value="">All report types</option>
            <?php foreach (['Lost', 'Found'] as $type): ?><option value="<?= e($type) ?>"<?= ($filters['type'] ?? '') === $type ? ' selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select class="input" id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (['Open', 'Claim Pending', 'Unclaimed', 'Returned'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="category">Category</label>
        <select class="input" id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?>
        </select>
    </div>
</form>

<div class="table-toolbar lf-toolbar">
    <div><div class="panel-title">Reported Items Directory</div><div class="cell-sub">Claims are verified at the OSAS office before an item is released.</div></div>
    <span class="badge badge--brand"><?= number_format(count($items)) ?> records</span>
</div>

<?php if ($items === []): ?>
    <div class="card panel-card"><div class="empty-state"><strong>No items match these filters.</strong></div></div>
<?php else: ?>
    <div class="lf-card-grid">
        <?php foreach ($items as $item): ?>
            <?php
            $status = (string) $item['status'];
            $tone = match ($status) {
                'Claim Pending' => 'red',
                'Returned' => 'green',
                'Lost' => 'amber',
                'Unclaimed' => 'amber',
                default => $item['type'] === 'Lost' ? 'amber' : 'blue',
            };
            $statusLabel = match ($status) {
                'Claim Pending' => 'Claim pending',
                'Returned' => 'Claimed',
                default => $item['type'],
            };
            $letter = mb_strtoupper(mb_substr((string) ($item['category'] ?: $item['title']), 0, 1));
            ?>
            <article class="lf-card lf-card--<?= e($tone) ?><?= !empty($item['image_file_id']) ? ' lf-card--has-image' : '' ?>" data-lf-card>
                <div class="lf-card__inner">
                    <div class="lf-card__front">
                        <div class="lf-card__hero">
                            <?php if (!empty($item['image_file_id'])): ?>
                                <button type="button" class="lf-card__flip" data-lf-flip aria-label="Show item photo"><?= App\Support\Icons::render('eye', 15) ?></button>
                            <?php endif; ?>
                            <span class="lf-card__letter"><?= e($letter) ?></span>
                            <span class="lf-card__status"><?= e($statusLabel) ?></span>
                        </div>
                        <div class="lf-card__body">
                            <div class="lf-card__meta"><?= e($item['public_id']) ?> &middot; <?= e(mb_strtoupper((string) $item['category'])) ?></div>
                            <a class="lf-card__title" href="<?= url('/student/lost-found/' . $item['public_id']) ?>"><?= e($item['title']) ?></a>
                            <ul class="lf-card__facts">
                                <li><?= App\Support\Icons::render('pin', 12) ?> <?= e($item['location'] ?: 'Location not recorded') ?></li>
                                <li><?= App\Support\Icons::render('calendar', 12) ?> <?= e(fmt_date($item['date_occurred'] ?: $item['posted_at'])) ?></li>
                                <li><?= App\Support\Icons::render('user', 12) ?> <?= e($item['reported_by']) ?></li>
                            </ul>
                            <div class="lf-card__actions">
                                <a class="btn btn--outline btn--sm" href="<?= url('/student/lost-found/' . $item['public_id']) ?>">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="lf-card__back">
                        <?php if (!empty($item['image_file_id'])): ?>
                            <img src="<?= url('/student/lost-found/' . $item['public_id'] . '/image') ?>" alt="<?= e($item['title']) ?> photo" loading="lazy">
                        <?php else: ?>
                            <div class="lf-card__no-image"><?= App\Support\Icons::render('eye', 24) ?><span>No photo attached</span></div>
                        <?php endif; ?>
                        <button type="button" class="btn btn--ghost btn--sm" data-lf-flip>Back</button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
=======
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><div class="page-head__actions"><a class="btn btn--primary" href="<?= url('/student/lost-found/report') ?>"><?= App\Support\Icons::render('plus', 14) ?> Report an Item</a></div></div>
<div class="stats">
    <div class="stat"><div class="stat__label">Open Items</div><div class="stat__value"><?= number_format($counts['open'] ?? 0) ?></div><div class="stat__sub">Lost or waiting for owners</div></div>
    <div class="stat"><div class="stat__label">Claims Pending</div><div class="stat__value"><?= number_format($counts['claim_pending'] ?? 0) ?></div><div class="stat__sub">Awaiting verification</div></div>
    <div class="stat"><div class="stat__label">My Reports</div><div class="stat__value"><?= number_format(count($myReports)) ?></div><div class="stat__sub">Items you submitted</div></div>
    <div class="stat"><div class="stat__label">My Claims</div><div class="stat__value"><?= number_format(count($myClaims)) ?></div><div class="stat__sub">Claims you filed</div></div>
</div>
<form class="filter-bar filter-bar--4" method="GET" action="<?= url('/student/lost-found') ?>">
    <div class="field"><label for="search">Search items</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Item, location or description"></span></div>
    <div class="field"><label for="type">Type</label><select class="input" id="type" name="type"><option value="">All types</option><?php foreach (['Lost', 'Found'] as $type): ?><option value="<?= e($type) ?>"<?= ($filters['type'] ?? '') === $type ? ' selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Open', 'Claim Pending', 'Unclaimed', 'Returned'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Filter</button><a class="btn btn--ghost" href="<?= url('/student/lost-found') ?>">Reset</a></div>
</form>
<?php if ($myReports !== [] || $myClaims !== []): ?>
<div class="dash-grid">
    <?php if ($myReports !== []): ?><section class="card panel-card"><div class="panel-title">My Reports</div><div class="mini-list"><?php foreach ($myReports as $report): ?><a class="mini-item" href="<?= url('/student/lost-found/' . $report['public_id']) ?>"><span class="log-item__ico"><?= App\Support\Icons::render('search', 13) ?></span><div><strong><?= e($report['title']) ?></strong><span><?= e($report['type']) ?> · <?= e($report['status']) ?> · <?= e(fmt_date($report['posted_at'])) ?></span></div></a><?php endforeach; ?></div></section><?php endif; ?>
    <?php if ($myClaims !== []): ?><section class="card panel-card"><div class="panel-title">My Claims</div><div class="mini-list"><?php foreach ($myClaims as $claim): ?><a class="mini-item" href="<?= url('/student/lost-found/' . $claim['item_code']) ?>"><span class="log-item__ico"><?= App\Support\Icons::render('checkCircle', 13) ?></span><div><strong><?= e($claim['item_title']) ?></strong><span><?= e($claim['item_code']) ?> · <?= e($claim['status']) ?></span></div></a><?php endforeach; ?></div></section><?php endif; ?>
</div>
<?php endif; ?>
<div class="lf-grid">
    <?php foreach ($items as $item): ?>
        <article class="card lf-card">
            <div class="lf-card__head"><span class="badge <?= $item['type'] === 'Lost' ? 'badge--high' : 'badge--info' ?>"><?= e($item['type']) ?></span><span class="badge <?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></div>
            <h3 class="lf-card__title"><a href="<?= url('/student/lost-found/' . $item['public_id']) ?>"><?= e($item['title']) ?></a></h3>
            <p class="lf-card__text"><?= e(App\Support\Html::summary($item['description'], 150)) ?></p>
            <div class="lf-card__meta"><span><?= App\Support\Icons::render('pin', 12) ?> <?= e($item['location']) ?></span><span><?= App\Support\Icons::render('clock', 12) ?> <?= e(fmt_date($item['date_occurred'])) ?></span></div>
            <a class="btn btn--outline btn--sm" href="<?= url('/student/lost-found/' . $item['public_id']) ?>">View Item</a>
        </article>
    <?php endforeach; ?>
</div>
<?php if ($items === []): ?><div class="card panel-card"><div class="empty-state"><strong>No lost or found items match these filters.</strong></div></div><?php endif; ?>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
