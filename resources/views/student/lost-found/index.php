<?php
/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $myReports */
/** @var list<array<string, mixed>> $myClaims */
/** @var array<string, string> $filters */
/** @var array<string, int> $counts */
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
