<?php
/** @var list<array<string, mixed>> $items */
/** @var array<string, mixed> $pagination */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
<<<<<<< HEAD
/** @var array<int, int> $claimCounts */
/** @var list<string> $categories */
?>
<div class="page-head">
    <div>
        <h2 class="page-head__title">Lost &amp; Found Management</h2>
        <p class="page-head__sub">Review reported lost and found items, verify ownership claims, and record items returned to students.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--outline" href="<?= url('/admin/lost-found/create') ?>"><?= App\Support\Icons::render('plus', 14) ?> Post Item</a>
        <a class="btn btn--primary" href="<?= url('/admin/lost-found/export') ?>"><?= App\Support\Icons::render('download', 14) ?> Export CSV</a>
    </div>
</div>

<div class="stats lf-stats">
    <div class="stat">
        <div class="stat__label">Open Reports</div>
        <div class="stat__value"><?= number_format($counts['open'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--info"><?= number_format($counts['total'] ?? 0) ?> total items</span> <?= number_format($counts['lost'] ?? 0) ?> lost &middot; <?= number_format($counts['found'] ?? 0) ?> found</div>
    </div>
    <div class="stat">
        <div class="stat__label">Claim Pending</div>
        <div class="stat__value"><?= number_format($counts['claim_pending'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--pending">Claim to verify</span> Ownership checks at the OSAS office</div>
    </div>
    <div class="stat">
        <div class="stat__label">Returned to Owner</div>
        <div class="stat__value"><?= number_format($counts['returned'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--verified">Verified hand-over</span> Recorded with claimant and date</div>
    </div>
    <div class="stat">
        <div class="stat__label">Unclaimed</div>
        <div class="stat__value"><?= number_format($counts['unclaimed'] ?? 0) ?></div>
        <div class="stat__sub"><span class="badge badge--draft">For disposal review</span> <?= number_format($counts['archived'] ?? 0) ?> archived reports</div>
    </div>
</div>

<form class="filter-bar lf-filters" method="GET" action="<?= url('/admin/lost-found') ?>">
    <div class="field">
        <label for="search">Search</label>
        <span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search code, item, reporter..."></span>
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
            <?php foreach (['Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
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
    <span class="badge badge--brand"><?= number_format($pagination['total']) ?> records</span>
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
                'Archived', 'Closed' => 'gray',
                default => $item['type'] === 'Lost' ? 'amber' : 'blue',
            };
            $statusLabel = match ($status) {
                'Claim Pending' => 'Claim pending',
                'Returned' => 'Claimed',
                default => $item['type'],
            };
            $letter = mb_strtoupper(mb_substr((string) ($item['category'] ?: $item['title']), 0, 1));
            $claimCount = (int) ($claimCounts[(int) $item['id']] ?? 0);
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
                            <a class="lf-card__title" href="<?= url('/admin/lost-found/' . $item['public_id']) ?>"><?= e($item['title']) ?></a>
                            <ul class="lf-card__facts">
                                <li><?= App\Support\Icons::render('pin', 12) ?> <?= e($item['location'] ?: 'Location not recorded') ?></li>
                                <li><?= App\Support\Icons::render('calendar', 12) ?> <?= e(fmt_date($item['date_occurred'] ?: $item['posted_at'])) ?></li>
                                <li><?= App\Support\Icons::render('user', 12) ?> <?= e($item['reported_by']) ?></li>
                            </ul>
                            <div class="lf-card__actions">
                                <a class="btn btn--outline btn--sm" href="<?= url('/admin/lost-found/' . $item['public_id']) ?>">View</a>
                                <?php if ($claimCount > 0): ?>
                                    <a class="btn btn--primary btn--sm" href="<?= url('/admin/lost-found/' . $item['public_id']) ?>">Verify (<?= number_format($claimCount) ?>)</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="lf-card__back">
                        <?php if (!empty($item['image_file_id'])): ?>
                            <img src="<?= url('/admin/lost-found/' . $item['public_id'] . '/image') ?>" alt="<?= e($item['title']) ?> photo" loading="lazy">
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

<?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/lost-found', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' items']) ?>
=======
/** @var list<array<string, mixed>> $pendingClaims */
/** @var list<string> $categories */
?><div class="page-head"><div><h2 class="page-head__title"><?= e($pageTitle) ?></h2><p class="page-head__sub"><?= e($pageSubtitle) ?></p></div><a class="btn btn--primary" href="<?= url('/admin/lost-found/create') ?>"><?= App\Support\Icons::render('plus', 14) ?> Post Item</a></div>
<div class="stats">
    <div class="stat"><div class="stat__label">Open Items</div><div class="stat__value"><?= number_format($counts['open'] ?? 0) ?></div><div class="stat__sub">Awaiting owner or claimant</div></div>
    <div class="stat"><div class="stat__label">Claim Pending</div><div class="stat__value"><?= number_format($counts['claim_pending'] ?? 0) ?></div><div class="stat__sub">Ownership verification needed</div></div>
    <div class="stat"><div class="stat__label">Returned</div><div class="stat__value"><?= number_format($counts['returned'] ?? 0) ?></div><div class="stat__sub">Released to owners</div></div>
    <div class="stat"><div class="stat__label">Archived</div><div class="stat__value"><?= number_format($counts['archived'] ?? 0) ?></div><div class="stat__sub">Closed records</div></div>
</div>
<?php if ($pendingClaims !== []): ?>
<section class="card panel-card claims-panel">
    <div class="panel-card__head"><div><div class="panel-title">Claims Awaiting Verification</div><p class="panel-card__sub">Review proof and approve or reject ownership claims.</p></div><span class="badge badge--pending"><?= count($pendingClaims) ?> pending</span></div>
    <div class="table-scroll"><table class="data"><thead><tr><th>Claimant</th><th>Item</th><th>Proof</th><th>Submitted</th><th class="u-right">Review</th></tr></thead><tbody>
    <?php foreach ($pendingClaims as $claim): ?>
        <tr><td><div class="cell-title"><?= e($claim['claimant_name']) ?></div><div class="cell-sub"><?= e($claim['claimant_email']) ?></div><div class="cell-sub"><?= e($claim['student_id']) ?></div></td><td><div class="cell-title"><?= e($claim['item_title']) ?></div><div class="cell-sub mono"><?= e($claim['item_code']) ?></div></td><td><div class="cell-sub"><?= e(App\Support\Html::summary($claim['proof'], 100)) ?></div></td><td><?= e(time_ago($claim['created_at'])) ?></td><td class="u-right"><a class="btn btn--outline btn--sm" href="<?= url('/admin/lost-found/' . $claim['item_code']) ?>">Review</a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>
<form class="filter-bar filter-bar--4" method="GET" action="<?= url('/admin/lost-found') ?>">
    <div class="field"><label for="search">Search</label><span class="input-group"><span class="input-group__ico"><?= App\Support\Icons::render('search', 15) ?></span><input class="input" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Item, location or reporter"></span></div>
    <div class="field"><label for="type">Type</label><select class="input" id="type" name="type"><option value="">All types</option><?php foreach (['Lost', 'Found'] as $type): ?><option value="<?= e($type) ?>"<?= ($filters['type'] ?? '') === $type ? ' selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">All statuses</option><?php foreach (['Open', 'Claim Pending', 'Unclaimed', 'Returned', 'Archived', 'Closed'] as $status): ?><option value="<?= e($status) ?>"<?= ($filters['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="category">Category</label><select class="input" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= ($filters['category'] ?? '') === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__actions"><button class="btn btn--primary" type="submit">Apply</button><a class="btn btn--ghost" href="<?= url('/admin/lost-found') ?>">Reset</a></div>
</form>
<div class="table-wrap">
    <div class="table-toolbar"><div><div class="panel-title">Lost & Found Records</div><div class="cell-sub">Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> items</div></div><span class="badge badge--brand"><?= number_format($pagination['total']) ?> records</span></div>
    <?php if ($items === []): ?><div class="empty-state"><strong>No items match these filters.</strong></div><?php else: ?>
        <div class="table-scroll"><table class="data"><thead><tr><th>Item</th><th>Type</th><th>Location</th><th>Status</th><th>Reported</th><th class="u-right">Actions</th></tr></thead><tbody>
        <?php foreach ($items as $item): ?>
            <tr><td><div class="identity-cell"><span class="avatar avatar--blue"><?= App\Support\Icons::render('search', 15) ?></span><div><a class="cell-title" href="<?= url('/admin/lost-found/' . $item['public_id']) ?>"><?= e($item['title']) ?></a><div class="cell-sub"><?= e($item['category']) ?> · <?= e($item['public_id']) ?></div><div class="cell-sub"><?= e($item['reported_by']) ?></div></div></div></td><td><span class="badge <?= $item['type'] === 'Lost' ? 'badge--high' : 'badge--info' ?>"><?= e($item['type']) ?></span></td><td><div class="cell-title"><?= e($item['location']) ?></div><div class="cell-sub"><?= e($item['custody']) ?></div></td><td><span class="badge <?= e(status_class($item['status'])) ?>"><?= e($item['status']) ?></span></td><td><div class="cell-title"><?= e(fmt_date($item['posted_at'])) ?></div><div class="cell-sub"><?= e(time_ago($item['posted_at'])) ?></div></td><td class="u-right"><div class="cell-actions"><a class="btn btn--ghost btn--sm" href="<?= url('/admin/lost-found/' . $item['public_id']) ?>">Open</a><a class="btn btn--outline btn--sm" href="<?= url('/admin/lost-found/' . $item['public_id'] . '/edit') ?>">Edit</a></div></td></tr>
        <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <?= App\Support\View::render('partials/pagination', ['pagination' => $pagination, 'baseUrl' => '/admin/lost-found', 'query' => $filters, 'label' => 'Showing ' . number_format($pagination['from']) . '–' . number_format($pagination['to']) . ' of ' . number_format($pagination['total']) . ' items']) ?>
</div>
>>>>>>> ec9a5edde5ad128ad622472a6352ad33eff5ac37
